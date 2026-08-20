<?php

namespace App\Http\Controllers;

use App\Exceptions\FiveSimException;
use App\Models\Order;
use App\Services\FiveSim\Contracts\FiveSimServiceInterface;
use App\Services\PricingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
                ]);

                $user->decrement('balance', $priceFcfa);

                return $order;
            });
        } catch (FiveSimException $e) {
            return back()->withErrors(['service' => $e->getMessage()])->withInput();
        }

        return redirect()->route('purchase.waiting', $order);
    }

    /**
     * Minimal placeholder: shows the purchased number and current status with no
     * countdown or SMS polling yet - action_06 builds the real waiting/receiving screen.
     */
    public function waiting(Order $order): View
    {
        abort_unless($order->user_id === auth()->id(), 403);

        return view('purchase.waiting', ['order' => $order]);
    }
}
