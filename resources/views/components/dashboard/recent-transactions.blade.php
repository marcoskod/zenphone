@props(['orders'])

<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex items-center justify-between">
        <h3 class="text-sm font-semibold text-secondary dark:text-light">Dernières transactions</h3>
        <a href="{{ route('history') }}" class="text-xs font-medium text-primary hover:text-primary/80">Voir tout</a>
    </div>

    <div class="mt-4 overflow-x-auto">
        @if ($orders->isEmpty())
            <p class="py-6 text-center text-sm text-secondary/60 dark:text-light/60">Aucune transaction pour le moment.</p>
        @else
            <table class="w-full text-left text-sm">
                <thead>
                    <tr class="text-xs uppercase text-secondary/50 dark:text-light/50">
                        <th class="pb-2 font-medium">Numéro</th>
                        <th class="pb-2 font-medium">Service</th>
                        <th class="pb-2 font-medium">Statut</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($orders as $order)
                        <tr>
                            <td class="py-2 text-secondary dark:text-light">{{ $order->phone }}</td>
                            <td class="py-2 capitalize text-secondary/80 dark:text-light/80">{{ $order->service }}</td>
                            <td class="py-2">
                                <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                    {{ ucfirst($order->status) }}
                                </span>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>
