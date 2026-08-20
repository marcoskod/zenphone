<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Historique') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form method="GET" action="{{ route('history.topups') }}" class="grid gap-3 sm:grid-cols-5">
                    <div>
                        <label for="date_from" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Du</label>
                        <input type="date" id="date_from" name="date_from" value="{{ request('date_from') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label for="date_to" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Au</label>
                        <input type="date" id="date_to" name="date_to" value="{{ request('date_to') }}" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label for="operator" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Opérateur</label>
                        <select id="operator" name="operator" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                            <option value="">Tous</option>
                            <option value="Orange Money" @selected(request('operator') === 'Orange Money')>Orange Money</option>
                            <option value="Wave" @selected(request('operator') === 'Wave')>Wave</option>
                            <option value="MTN MoMo" @selected(request('operator') === 'MTN MoMo')>MTN MoMo</option>
                            <option value="Moov Money" @selected(request('operator') === 'Moov Money')>Moov Money</option>
                        </select>
                    </div>
                    <div>
                        <label for="status" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Statut</label>
                        <select id="status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                            <option value="">Tous</option>
                            <option value="pending" @selected(request('status') === 'pending')>En attente</option>
                            <option value="confirmed" @selected(request('status') === 'confirmed')>Confirmée</option>
                            <option value="failed" @selected(request('status') === 'failed')>Échouée</option>
                        </select>
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="inline-flex flex-1 items-center justify-center gap-2 rounded-xl bg-primary px-4 py-2 text-sm font-semibold text-white transition hover:bg-primary/90">
                            <i class="fa-solid fa-filter"></i>
                            Filtrer
                        </button>
                        @if (request()->hasAny(['date_from', 'date_to', 'operator', 'status']))
                            <a href="{{ route('history.topups') }}" class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-2 text-sm text-secondary transition hover:bg-slate-50 dark:border-slate-700 dark:text-light dark:hover:bg-slate-800">
                                <i class="fa-solid fa-xmark"></i>
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-4 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-secondary dark:text-light">Mes recharges</h3>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-secondary/50 dark:text-light/50">
                                <th class="px-4 py-3 font-medium">Date</th>
                                <th class="px-4 py-3 font-medium">Montant</th>
                                <th class="px-4 py-3 font-medium">Opérateur</th>
                                <th class="px-4 py-3 font-medium">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($topups as $topup)
                                <tr>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ $topup->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 font-medium text-secondary dark:text-light">
                                        {{ number_format($topup->amount_fcfa, 0, ',', ' ') }} FCFA
                                    </td>
                                    <td class="px-4 py-3 text-secondary/80 dark:text-light/80">{{ $topup->operator ?? '—' }}</td>
                                    <td class="px-4 py-3">
                                        <span class="inline-flex items-center rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">
                                            {{ ucfirst($topup->status) }}
                                        </span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-4 py-10 text-center text-sm text-secondary/60 dark:text-light/60">
                                        Aucune recharge pour le moment.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="border-t border-slate-100 p-4 dark:border-slate-800">
                    {{ $topups->links() }}
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
