<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Topup;
use App\Models\User;
use App\Services\PricingService;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __construct(protected PricingService $pricing)
    {
    }

    public function index(): View
    {
        return view('admin.dashboard', [
            'stats' => $this->computeStats(),
        ]);
    }

    /**
     * "Active users" = users with at least one order or confirmed topup created in the
     * last 30 days. The app doesn't currently record a last-login timestamp anywhere
     * (Breeze's session login, the single-page quick-auth endpoint, and Google OAuth all
     * authenticate without logging it), so rather than adding that tracking across three
     * separate auth entry points just for this one admin metric, "active" is defined
     * purely from existing transactional data.
     */
    private function computeStats(): array
    {
        $since = Carbon::now()->subDays(30);

        $activeUsers = User::where(function ($query) use ($since) {
            $query->whereHas('orders', fn ($q) => $q->where('created_at', '>=', $since))
                ->orWhereHas('topups', fn ($q) => $q->where('status', 'confirmed')->where('created_at', '>=', $since));
        })->count();

        $ordersRevenue = (float) Order::sum('price_fcfa');
        $topupsRevenue = (float) Topup::where('status', 'confirmed')->sum('amount_fcfa');

        $transactionCount = Order::count() + Topup::where('status', 'confirmed')->count();

        return [
            'active_users' => $activeUsers,
            'total_revenue' => $ordersRevenue + $topupsRevenue,
            'transaction_count' => $transactionCount,
            // Reconstructing the actual per-order margin would require the original
            // 5sim USD cost stored alongside each order, which isn't captured today
            // (orders only stores price_fcfa, the already-marked-up customer price) -
            // so this reports the currently configured margin (admin setting if one
            // exists, else FIVESIM_MARGIN_PERCENT) rather than a computed average.
            // Documented limitation, not a bug.
            'average_margin_percent' => $this->pricing->marginPercent(),
        ];
    }
}
