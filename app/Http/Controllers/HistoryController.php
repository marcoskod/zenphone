<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HistoryController extends Controller
{
    public function orders(Request $request): View
    {
        $orders = $this->applyOrderFilters($request->user()->orders()->latest(), $request)
            ->get();

        return view('history.orders', ['orders' => $orders]);
    }

    public function topups(Request $request): View
    {
        $topups = $this->applyTopupFilters($request->user()->topups()->latest(), $request)
            ->get();

        return view('history.topups', ['topups' => $topups]);
    }

    private function applyOrderFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->when($request->filled('service'), fn (Builder $q) => $q->where('service', $request->input('service')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')));
    }

    private function applyTopupFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->when($request->filled('operator'), fn (Builder $q) => $q->where('operator', $request->input('operator')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')));
    }
}
