<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        ]);
    }
}
