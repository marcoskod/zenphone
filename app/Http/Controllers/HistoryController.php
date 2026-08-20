<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function orders(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->latest()
            ->get();

        return view('history.orders', ['orders' => $orders]);
    }
}
