<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Topup;
use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Shown as two separate filterable/paginated tables (orders, topups) rather than one
     * merged list - Eloquent doesn't paginate a UNION of two different models cleanly,
     * and the two transaction kinds have different columns (service/country/phone vs
     * operator/phone_number), so keeping them apart is both simpler and clearer to read.
     */
    public function index(Request $request): View
    {
        $orders = $this->applyFilters(
            Order::query()->with('user')->latest(),
            $request,
        )->paginate(20, ['*'], 'orders_page')->appends($request->query());

        $topups = $this->applyFilters(
            Topup::query()->with('user')->latest(),
            $request,
        )->paginate(20, ['*'], 'topups_page')->appends($request->query());

        return view('admin.transactions.index', [
            'orders' => $orders,
            'topups' => $topups,
            'filters' => $request->only(['user', 'date_from', 'date_to', 'status']),
        ]);
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->filled('user'), function (Builder $q) use ($request) {
                $term = $request->string('user');

                $q->whereHas('user', function (Builder $userQuery) use ($term) {
                    $userQuery->where('name', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%");
                });
            })
            ->when($request->filled('date_from'), fn (Builder $q) => $q->whereDate('created_at', '>=', $request->input('date_from')))
            ->when($request->filled('date_to'), fn (Builder $q) => $q->whereDate('created_at', '<=', $request->input('date_to')))
            ->when($request->filled('status'), fn (Builder $q) => $q->where('status', $request->input('status')));
    }
}
