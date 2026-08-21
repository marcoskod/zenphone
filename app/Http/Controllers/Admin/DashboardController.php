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
            'chartData' => $this->getRevenueChartData(),
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

    /**
     * Revenue (orders + confirmed topups) and an approximated margin, grouped by day
     * over the last 30 days. The margin line is derived from that day's order revenue
     * using the CURRENT margin percent (topups aren't a marked-up 5sim cost, so they're
     * excluded from it) - like average_margin_percent above, this is an approximation
     * since historical per-order margin isn't stored, not a true day-by-day
     * reconstruction of what the margin actually was on that day.
     */
    private function getRevenueChartData(): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();
        $marginPercent = $this->pricing->marginPercent();

        $ordersByDay = Order::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(price_fcfa) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $topupsByDay = Topup::where('status', 'confirmed')
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(amount_fcfa) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $revenue = [];
        $margin = [];

        for ($date = $start->copy(); $date->lte(Carbon::now()); $date->addDay()) {
            $key = $date->toDateString();
            $orderRevenue = (float) ($ordersByDay[$key] ?? 0);
            $topupRevenue = (float) ($topupsByDay[$key] ?? 0);

            $labels[] = $date->format('d/m');
            $revenue[] = $orderRevenue + $topupRevenue;
            // price_fcfa = cost * (1 + margin/100), so the margin portion of price_fcfa
            // is price_fcfa * margin / (100 + margin).
            $margin[] = round($orderRevenue * $marginPercent / (100 + $marginPercent), 2);
        }

        return ['labels' => $labels, 'revenue' => $revenue, 'margin' => $margin];
    }
}
