<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Historique') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form method="GET" action="{{ route('history') }}" class="grid gap-3 sm:grid-cols-5">
                    <div>
                        <label for="date_from" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Du</label>
                        <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label for="date_to" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Au</label>
                        <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label for="service" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Service</label>
                        <input type="text" id="service" name="service" value="{{ request('service') }}" placeholder="whatsapp" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Statut</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                            <option value="">Tous</option>
                            <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                            <option value="received" @selected(request('status') === 'received')>Reçu</option>
                            <option value="cancelled" @selected(request('status') === 'cancelled')>Annulé</option>
                            <option value="finished" @selected(request('status') === 'finished')>Terminé</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90">
                            <i class="fa-solid fa-filter"></i>
                            Filtrer
                        </button>
                        @if (request()->hasAny(['date_from', 'date_to', 'service', 'status']))
                            <a href="{{ route('history') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-2 text-sm text-secondary transition hover:bg-slate-50 dark:border-slate-700 dark:text-light dark:hover:bg-slate-800">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

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

                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $orders->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
