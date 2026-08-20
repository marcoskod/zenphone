<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $stats = [
            'numbers_bought' => $user->orders()->count(),
            'sms_received' => $user->orders()->whereNotNull('sms_code')->count(),
            'balance' => $user->balance,
            'total_spent' => (float) $user->orders()->sum('price_fcfa'),
        ];

        return view('dashboard', [
            'stats' => $stats,
            'chartData' => $this->getActivityChartData($user),
        ]);
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
