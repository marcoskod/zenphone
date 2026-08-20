<?php

namespace App\Http\Controllers;

use App\Exceptions\FiveSimException;
use App\Models\Order;
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

        $user = $request->user();

        try {
            $products = $this->fiveSim->getProducts($validated['country'], 'any');
        } catch (FiveSimException $e) {
            return back()->withErrors(['service' => $e->getMessage()])->withInput();
        }

        $product = $products[$validated['service']] ?? null;

        if (! is_array($product) || ! isset($product['Price'])) {
            return back()
                ->withErrors(['service' => "Ce service n'est pas disponible pour ce pays."])
                ->withInput();
        }

        $priceFcfa = $this->pricing->calculatePrice((float) $product['Price']);

        // Checked (and, on failure, returned) before any 5sim call is made, so an
        // insufficient balance never triggers a buyActivation() request.
        if ($user->balance < $priceFcfa) {
            return back()
                ->withErrors(['balance' => 'Solde insuffisant pour cet achat.'])
                ->with('insufficient_balance', true)
                ->withInput();
        }

        try {
            $order = DB::transaction(function () use ($user, $validated, $priceFcfa) {
                // buyActivation() runs inside the transaction: if it throws, neither the
                // order row nor the balance deduction below is ever committed, so a
                // failed 5sim call never leaves the user debited.
                $activation = $this->fiveSim->buyActivation($validated['country'], 'any', $validated['service']);

                $order = Order::create([
                    'user_id' => $user->id,
                    'fivesim_order_id' => $activation['id'],
                    'service' => $validated['service'],
                    'country' => $validated['country'],
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
            return back()->withErrors(['service' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchase.waiting', $order);
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

        $order->update([
            'status' => strtolower($result['status'] ?? $order->status),
            // Never clobber an already-received code with a blank result from a later poll.
            'sms_code' => $smsCode ?? $order->sms_code,
        ]);

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
