<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Historique') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-4 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-secondary dark:text-light">Mes numéros</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-secondary/50 dark:text-light/50">
                                <th class="px-4 py-3 font-medium">Date</th>
                                <th class="px-4 py-3 font-medium">Service</th>
                                <th class="px-4 py-3 font-medium">Pays</th>
                                <th class="px-4 py-3 font-medium">Numéro</th>
                                <th class="px-4 py-3 font-medium">Statut</th>
                                <th class="px-4 py-3 font-medium">SMS reçu</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 capitalize text-secondary/80 dark:text-light/80">{{ $order->service }}</td>
                                    <td class="px-4 py-3 capitalize text-secondary/80 dark:text-light/80">{{ str_replace('_', ' ', $order->country) }}</td>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ $order->phone }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                            {{ ucfirst($order->status) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3">
                                        @if ($order->sms_code)
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-success">
                                                <i class="fa-solid fa-check"></i> Reçu
                                            </span>
                                        @else
                                            <span class="inline-flex items-center gap-1 text-xs font-medium text-secondary/50 dark:text-light/50">
                                                <i class="fa-solid fa-xmark"></i> Non reçu
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-4 py-10 text-center text-sm text-secondary/60 dark:text-light/60">
                                        Aucune commande trouvée.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
