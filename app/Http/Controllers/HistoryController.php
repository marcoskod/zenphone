<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class HistoryController extends Controller
{
    public function orders(Request $request): View
    {
        $orders = $this->applyOrderFilters($request->user()->orders()->latest(), $request)
            ->paginate(20)
            ->appends($request->query());

        return view('history.orders', ['orders' => $orders]);
    }

    public function topups(Request $request): View
    {
        $topups = $this->applyTopupFilters($request->user()->topups()->latest(), $request)
            ->paginate(20)
            ->appends($request->query());

        return view('history.topups', ['topups' => $topups]);
    }

    /**
     * GET /historique/export - the current filters (same query params as orders())
     * apply here too, so exporting after filtering only downloads the filtered rows.
     */
    public function exportCsv(Request $request): StreamedResponse
    {
        $orders = $this->applyOrderFilters($request->user()->orders()->latest(), $request)->get();

        $filename = 'commandes-'.now()->format('Y-m-d-His').'.csv';

        $callback = function () use ($orders) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Date', 'Service', 'Pays', 'Numéro', 'Statut', 'SMS reçu', 'Prix (FCFA)']);

            foreach ($orders as $order) {
                fputcsv($handle, [
                    $order->created_at->format('Y-m-d H:i'),
                    $order->service,
                    $order->country,
                    $order->phone,
                    $order->status,
                    $order->sms_code ? 'Oui' : 'Non',
                    $order->price_fcfa,
                ]);
            }

            fclose($handle);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
        ]);
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
