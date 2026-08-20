<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        return view('dashboard', [
            'stats' => $this->computeStats($user),
            'chartData' => $this->getActivityChartData($user),
            'recentOrders' => $this->recentOrders($user),
        ]);
    }

    /**
     * GET /api/dashboard - JSON counterpart of index(), used by the single-page
     * homepage's "Mon compte" modal. Reuses computeStats()/getActivityChartData()/
     * recentOrders() rather than duplicating the underlying queries.
     */
    public function json(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'stats' => $this->computeStats($user),
            'chart_data' => $this->getActivityChartData($user),
            'recent_orders' => $this->recentOrders($user)->map(fn (Order $order) => [
                'id' => $order->id,
                'service' => $order->service,
                'country' => $order->country,
                'phone' => $order->phone,
                'status' => $order->status,
                'sms_received' => (bool) $order->sms_code,
                'price_fcfa' => (float) $order->price_fcfa,
                'created_at' => $order->created_at->toIso8601String(),
            ]),
        ]);
    }

    private function computeStats(User $user): array
    {
        return [
            'numbers_bought' => $user->orders()->count(),
            'sms_received' => $user->orders()->whereNotNull('sms_code')->count(),
            'balance' => (float) $user->balance,
            'total_spent' => (float) $user->orders()->sum('price_fcfa'),
        ];
    }

    private function recentOrders(User $user, int $limit = 5)
    {
        return $user->orders()->latest()->take($limit)->get();
    }

    /**
     * Spend per day for the last 30 days (today included), zero-filled for days
     * without any orders, keyed as Chart.js-friendly labels/data arrays.
     */
    private function getActivityChartData(User $user): array
    {
        $start = Carbon::now()->subDays(29)->startOfDay();

        $spendByDay = $user->orders()
            ->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as day, SUM(price_fcfa) as total')
            ->groupBy('day')
            ->pluck('total', 'day');

        $labels = [];
        $data = [];

        for ($date = $start->copy(); $date->lte(Carbon::now()); $date->addDay()) {
            $key = $date->toDateString();
            $labels[] = $date->format('d/m');
            $data[] = (float) ($spendByDay[$key] ?? 0);
        }

        return ['labels' => $labels, 'data' => $data];
    }
}
