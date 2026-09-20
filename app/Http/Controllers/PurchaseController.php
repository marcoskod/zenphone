<?php

namespace App\Http\Controllers;

use App\Exceptions\FedaPayException;
use App\Exceptions\SmsProviderException;
use App\Models\Order;
use App\Models\PendingPurchase;
use App\Models\User;
use App\Notifications\SmsReceived;
use App\Services\FedaPay\FedaPayService;
use App\Services\SmsProvider\Contracts\SmsProviderInterface;
use App\Services\OrderRefunder;
use App\Services\PricingService;
use App\Services\PurchaseFinalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PurchaseController extends Controller
{
    /** the supplier statuses meaning the number is dead and (with no SMS received) was refunded to us. */
    private const DEAD_STATUSES = ['canceled', 'cancelled', 'timeout', 'banned'];

    public function __construct(
        protected SmsProviderInterface $sms,
        protected PricingService $pricing,
        protected FedaPayService $fedaPay,
        protected PurchaseFinalizer $finalizer,
        protected OrderRefunder $refunder,
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

        return response()->json($this->orderJson($result['order']));
    }

    /**
     * POST /api/purchase/pay-init - first step of the direct-payment flow (no wallet
     * top-up involved): prices the service+country against live the supplier pricing and, if the
     * user's balance already covers it (e.g. a refund credit from a cancelled order),
     * buys and creates the order immediately, same as storeJson(). Otherwise it locks the
     * price into a PendingPurchase row and hands the frontend everything it needs to open
     * the FedaPay checkout widget for that exact amount - no separate "recharger mon
     * solde" step, no arbitrary amount picker.
     */
    public function payInit(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'service' => ['required', 'string'],
            'country' => ['required', 'string'],
        ]);

        $user = $request->user();
        $priced = $this->priceFor($validated['service'], $validated['country']);

        if (! $priced['success']) {
            return response()->json([
                'message' => $priced['error'],
                'field' => $priced['field'],
            ], $priced['status']);
        }

        $priceFcfa = $priced['price_fcfa'];

        if ($user->balance >= $priceFcfa) {
            $result = $this->attemptPurchase($user, $validated['service'], $validated['country']);

            if (! $result['success']) {
                return response()->json([
                    'message' => $result['error'],
                    'field' => $result['field'],
                ], $result['status']);
            }

            return response()->json(['paid_with' => 'balance'] + $this->orderJson($result['order']));
        }

        $pending = PendingPurchase::create([
            'user_id' => $user->id,
            'service' => $validated['service'],
            'country' => $validated['country'],
            'price_fcfa' => $priceFcfa,
            'status' => 'awaiting_payment',
        ]);

        return response()->json([
            'paid_with' => 'fedapay',
            'pending_purchase_id' => $pending->id,
            'amount' => (float) $priceFcfa,
            'description' => "Numéro {$validated['service']} ({$validated['country']}) — Zen_Sms",
        ]);
    }

    /**
     * POST /api/purchase/pay-confirm - called once the FedaPay checkout.js widget reports
     * an APPROVED result for a pay-init'd PendingPurchase. Never trusts the client alone:
     * verifies the transaction server-to-server (same FedaPayService used by the top-up
     * flow) and only buys the the supplier activation once FedaPay itself confirms status
     * "approved" for an amount covering the price locked in at pay-init time.
     *
     * If the the supplier purchase fails AFTER a real payment was captured, the customer's money
     * is never simply lost: it's credited to their balance as store credit (the same
     * mechanism the cancel/refund flow already uses) so they can retry immediately.
     */
    public function payConfirm(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'pending_purchase_id' => ['required', 'integer'],
            'transaction_id' => ['required', 'string'],
        ]);

        $user = $request->user();

        $pending = PendingPurchase::where('id', $validated['pending_purchase_id'])
            ->where('user_id', $user->id)
            ->first();

        if (! $pending) {
            return response()->json(['message' => 'Commande introuvable.'], 404);
        }

        // Idempotent: a retried confirm call (e.g. a flaky network response after the
        // first one already succeeded) just returns the order that was already created.
        if ($pending->status === 'paid' && $pending->order_id) {
            return response()->json($this->orderJson($pending->order));
        }

        if ($pending->status !== 'awaiting_payment') {
            return response()->json(['message' => 'Cette commande a déjà été traitée.'], 422);
        }

        try {
            $transaction = $this->fedaPay->verifyTransaction($validated['transaction_id']);
        } catch (FedaPayException $e) {
            return response()->json(['message' => $e->getMessage()], 502);
        }

        $result = $this->finalizer->finalize($pending, $validated['transaction_id'], $transaction);

        if (! $result['ok']) {
            return response()->json(['message' => $result['message']], $result['status']);
        }

        $order = $result['order'];

        return response()->json($this->orderJson($order));
    }

    private function orderJson(Order $order): array
    {
        return [
            'order_id' => $order->id,
            'phone' => $order->phone,
            'status' => $order->status,
            'expires_at' => $order->expires_at?->toIso8601String(),
            'waiting_url' => route('purchase.waiting', $order),
        ];
    }

    /**
     * Validates service+country against live the supplier pricing, checks the user's balance
     * (never calling buyActivation() if it's insufficient), and - if sufficient - buys
     * the activation and creates the Order inside a DB transaction. Shared by the
     * redirect-based store() and the JSON storeJson(), so both surfaces (the existing
     * /acheter page and the new single-page homepage) get identical behavior.
     *
     * @return array{success: true, order: Order}|array{success: false, field: string, error: string, status: int}
     */
    private function attemptPurchase(User $user, string $service, string $country): array
    {
        $priced = $this->priceFor($service, $country);

        if (! $priced['success']) {
            return $priced;
        }

        $priceFcfa = $priced['price_fcfa'];

        // Checked (and, on failure, returned) before any the supplier call is made, so an
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
                // Debit first, atomically and conditionally: two parallel requests can both
                // pass the unlocked balance check above, but only one UPDATE can find enough
                // balance left, so a balance can never fund two purchases (or go negative).
                // If buyActivation() then throws, the whole transaction - debit included -
                // rolls back, so a failed the supplier call never leaves the user debited.
                $debited = User::whereKey($user->id)
                    ->where('balance', '>=', $priceFcfa)
                    ->decrement('balance', $priceFcfa);

                if ($debited === 0) {
                    return null;
                }

                $activation = $this->sms->buyActivation($country, $service);

                $order = Order::create([
                    'user_id' => $user->id,
                    'provider_order_id' => $activation['id'],
                    'service' => $service,
                    'country' => $country,
                    'phone' => $activation['phone'] ?? '',
                    'price_fcfa' => $priceFcfa,
                    'status' => strtolower($activation['status'] ?? 'pending'),
                    'sms_code' => null,
                    'expires_at' => $this->parseExpiresAt($activation),
                ]);

                return $order;
            });
        } catch (SmsProviderException $e) {
            return ['success' => false, 'field' => 'service', 'error' => $e->getMessage(), 'status' => 502];
        }

        if ($order === null) {
            return ['success' => false, 'field' => 'balance', 'error' => 'Solde insuffisant pour cet achat.', 'status' => 422];
        }

        return ['success' => true, 'order' => $order];
    }

    /**
     * Looks up a service+country against live supplier pricing and converts to FCFA. Shared
     * by attemptPurchase() (balance path) and payInit() (direct-payment path) so both
     * price the exact same way and neither trusts a client-supplied price.
     *
     * @return array{success: true, price_fcfa: float}|array{success: false, field: string, error: string, status: int}
     */
    private function priceFor(string $service, string $country): array
    {
        try {
            $priceUsd = $this->sms->getPrice($country, $service);
        } catch (SmsProviderException $e) {
            return ['success' => false, 'field' => 'service', 'error' => $e->getMessage(), 'status' => 502];
        }

        if ($priceUsd === null) {
            return [
                'success' => false,
                'field' => 'service',
                'error' => "Ce service n'est pas disponible pour ce pays.",
                'status' => 404,
            ];
        }

        return ['success' => true, 'price_fcfa' => $this->pricing->calculatePrice($priceUsd)];
    }

    public function waiting(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('purchase.waiting', ['order' => $order]);
    }

    /**
     * GET /api/orders/{order}/status - polled by the waiting screen every 5 seconds.
     * This is where the Order row's status/sms_code finally get synced from the supplier (a
     * known gap since action_11, since no controller called checkOrder() until now).
     */
    public function status(Order $order): JsonResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        try {
            $result = $this->sms->checkOrder($order->provider_order_id);
        } catch (SmsProviderException $e) {
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

        // the supplier expired/cancelled the number with no SMS: it already refunded us, so pass
        // that on to the customer now instead of waiting for them to hit "Annuler" (which
        // the supplier would reject for an already-expired order).
        if ($order->sms_code === null && in_array($order->status, self::DEAD_STATUSES, true)) {
            $this->refunder->refund($order, $order->status === 'banned' ? 'banned' : 'timeout');
        }

        return response()->json([
            'status' => $order->status,
            'sms_code' => $order->sms_code,
            // Included so the frontend can rebuild the waiting screen from just an
            // order id after a page reload, without a separate lookup endpoint.
            'phone' => $order->phone,
            'expires_at' => $order->expires_at?->toIso8601String(),
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
     * refunds/marks the order cancelled once the supplier itself confirms the cancellation
     * (status CANCELED); it rejects cancellation once an SMS has already arrived, so that
     * case surfaces as a normal SmsProviderException here rather than needing a guard.
     */
    public function cancel(Order $order): JsonResponse
    {
        abort_unless($order->user_id === auth()->id(), 403);

        try {
            $result = $this->sms->cancelOrder($order->provider_order_id);
        } catch (SmsProviderException $e) {
            // the supplier refuses to cancel an order that already expired ("order expired") - but
            // an expired order with no SMS is exactly one the customer is owed a refund
            // for, so confirm its real state before surfacing the error.
            try {
                $result = $this->sms->checkOrder($order->provider_order_id);
            } catch (SmsProviderException) {
                return response()->json(['message' => $e->getMessage()], 502);
            }

            $hasSms = ! empty($result['sms']);

            if ($hasSms || ! in_array(strtolower($result['status'] ?? ''), self::DEAD_STATUSES, true)) {
                return response()->json(['message' => $e->getMessage()], 502);
            }
        }

        $status = strtolower($result['status'] ?? '');

        if (! in_array($status, ['canceled', 'cancelled', ...self::DEAD_STATUSES], true)) {
            return response()->json([
                'message' => "Le fournisseur n'a pas confirmé l'annulation de cette commande.",
            ], 422);
        }

        $this->refunder->refund($order, 'cancelled');

        return response()->json(['status' => 'cancelled']);
    }

    /**
     * the supplier's buyActivation() response includes an "expires" timestamp; fall back to a
     * 15-minute window (within the supplier's documented 5-20 minute activation range) if it's
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
