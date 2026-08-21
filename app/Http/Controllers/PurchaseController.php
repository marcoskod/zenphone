<?php

namespace App\Http\Controllers;

use App\Exceptions\FiveSimException;
use App\Models\Order;
use App\Models\User;
use App\Notifications\SmsReceived;
use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use App\Services\PricingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    public function __construct(
        protected FiveSimServiceInterface $fiveSim,
        protected PricingService $pricing,
    ) {
    }

    public function index(Request $request): View
    {
        return view('purchase.index', [
            'prefilledService' => (string) $request->query('service', ''),
            'prefilledCountry' => (string) $request->query('country', ''),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'service' => ['required', 'string'],
            'country' => ['required', 'string'],
        ]);

        $result = $this->attemptPurchase($request->user(), $validated['service'], $validated['country']);

        if (! $result['success']) {
            $errors = [$result['field'] => $result['error']];
            $response = back()->withErrors($errors)->withInput();

            if ($result['field'] === 'balance') {
                $response->with('insufficient_balance', true);
            }

            return $response;
        }

        return redirect()->route('purchase.waiting', $result['order']);
    }

    /**
     * POST /api/purchase - JSON counterpart of store(), used by the single-page
     * homepage's "Acheter maintenant" flow. Reuses attemptPurchase() rather than
     * duplicating the balance-check/buyActivation/order-creation logic.
     */
    public function storeJson(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service' => ['required', 'string'],
            'country' => ['required', 'string'],
        ]);

        $result = $this->attemptPurchase($request->user(), $validated['service'], $validated['country']);

        if (! $result['success']) {
            return response()->json([
                'message' => $result['error'],
                'field' => $result['field'],
            ], $result['status']);
        }

        $order = $result['order'];

        return response()->json([
            'order_id' => $order->id,
            'phone' => $order->phone,
            'status' => $order->status,
            'expires_at' => $order->expires_at?->toIso8601String(),
            'waiting_url' => route('purchase.waiting', $order),
        ]);
    }

    /**
     * Validates service+country against live 5sim pricing, checks the user's balance
     * (never calling buyActivation() if it's insufficient), and - if sufficient - buys
     * the activation and creates the Order inside a DB transaction. Shared by the
     * redirect-based store() and the JSON storeJson(), so both surfaces (the existing
     * /acheter page and the new single-page homepage) get identical behavior.
     *
     * @return array{success: true, order: Order}|array{success: false, field: string, error: string, status: int}
     */
    private function attemptPurchase(User $user, string $service, string $country): array
    {
        try {
            $products = $this->fiveSim->getProducts($country, 'any');
        } catch (FiveSimException $e) {
            return ['success' => false, 'field' => 'service', 'error' => $e->getMessage(), 'status' => 502];
        }

        $product = $products[$service] ?? null;

        if (! is_array($product) || ! isset($product['Price'])) {
            return [
                'success' => false,
                'field' => 'service',
                'error' => "Ce service n'est pas disponible pour ce pays.",
                'status' => 404,
            ];
        }

        $priceFcfa = $this->pricing->calculatePrice((float) $product['Price']);

        // Checked (and, on failure, returned) before any 5sim call is made, so an
        // insufficient balance never triggers a buyActivation() request.
        if ($user->balance < $priceFcfa) {
            return [
                'success' => false,
                'field' => 'balance',
                'error' => 'Solde insuffisant pour cet achat.',
                'status' => 422,
            ];
        }

        try {
            $order = DB::transaction(function () use ($user, $service, $country, $priceFcfa) {
                // buyActivation() runs inside the transaction: if it throws, neither the
                // order row nor the balance deduction below is ever committed, so a
                // failed 5sim call never leaves the user debited.
                $activation = $this->fiveSim->buyActivation($country, 'any', $service);

                $order = Order::create([
                    'user_id' => $user->id,
                    'fivesim_order_id' => $activation['id'],
                    'service' => $service,
                    'country' => $country,
                    'phone' => $activation['phone'] ?? '',
                    'price_fcfa' => $priceFcfa,
                    'status' => strtolower($activation['status'] ?? 'pending'),
                    'sms_code' => null,
                    'expires_at' => $this->parseExpiresAt($activation),
                ]);

                $user->decrement('balance', $priceFcfa);

                return $order;
            });
        } catch (FiveSimException $e) {
            return ['success' => false, 'field' => 'service', 'error' => $e->getMessage(), 'status' => 502];
        }

        return ['success' => true, 'order' => $order];
    }

    public function waiting(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('purchase.waiting', ['order' => $order]);
    }

    /**
     * GET /api/orders/{order}/status - polled by the waiting screen every 5 seconds.
     * This is where the Order row's status/sms_code finally get synced from 5sim (a
     * known gap since action_11, since no controller called checkOrder() until now).
     */
    public function status(Order $order): JsonResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        try {
            $result = $this->fiveSim->checkOrder($order->fivesim_order_id);
        } catch (FiveSimException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $smsCode = $this->extractSmsCode($result);

        // Captured before update() runs, while $order->sms_code still holds the
        // pre-poll value - this is what lets us detect a genuine no-code -> has-code
        // transition below rather than firing on every poll that simply re-confirms
        // an already-received code.
        $justReceived = $order->sms_code === null && $smsCode !== null;

        $order->update([
            'status' => strtolower($result['status'] ?? $order->status),
            // Never clobber an already-received code with a blank result from a later poll.
            'sms_code' => $smsCode ?? $order->sms_code,
        ]);

        if ($justReceived) {
            $order->user->notify(new SmsReceived($order));
        }

        return response()->json([
            'status' => $order->status,
            'sms_code' => $order->sms_code,
        ]);
    }

    private function extractSmsCode(array $result): ?string
    {
        if (empty($result['sms']) || ! is_array($result['sms'])) {
            return null;
        }

        $lastSms = end($result['sms']);

        return is_array($lastSms) ? ($lastSms['code'] ?? null) : null;
    }

    /**
     * POST /commande/{order}/annuler - called via fetch from the waiting screen (both
     * the explicit cancel button and the timeout state reuse this same endpoint). Only
     * refunds/marks the order cancelled once 5sim itself confirms the cancellation
     * (status CANCELED); 5sim rejects cancellation once an SMS has already arrived
     * ("order has sms"), so that case surfaces as a normal FiveSimException here rather
     * than needing a separate guard.
     */
    public function cancel(Order $order): JsonResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        try {
            $result = $this->fiveSim->cancelOrder($order->fivesim_order_id);
        } catch (FiveSimException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $status = strtolower($result['status'] ?? '');

        if (! in_array($status, ['canceled', 'cancelled'], true)) {
            return response()->json([
                'message' => "5sim n'a pas confirmé l'annulation de cette commande.",
            ], 422);
        }

        DB::transaction(function () use ($order) {
            $order->update(['status' => 'cancelled']);
            $order->user()->increment('balance', $order->price_fcfa);
        });

        return response()->json(['status' => 'cancelled']);
    }

    /**
     * 5sim's buyActivation() response includes an "expires" timestamp; fall back to a
     * 15-minute window (within 5sim's documented 5-20 minute activation range) if it's
     * missing or unparseable, rather than leaving the countdown undefined.
     */
    private function parseExpiresAt(array $activation): Carbon
    {
        if (isset($activation['expires'])) {
            try {
                return Carbon::parse($activation['expires']);
            } catch (\Throwable) {
                // fall through to the default below
            }
        }

        return now()->addMinutes(15);
    }
}
