<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Administration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('admin.partials.nav')

            <div class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <form method="GET" action="{{ route('admin.transactions') }}" class="grid gap-3 sm:grid-cols-5">
                    <div>
                        <label class="mb-1 block text-xs font-medium text-secondary/70 dark:text-light/70">Utilisateur</label>
                        <input type="text" name="user" value="{{ $filters['user'] ?? '' }}" placeholder="Nom ou email" class="w-full rounded-lg border-2 border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-secondary/70 dark:text-light/70">Du</label>
                        <input type="date" name="date_from" value="{{ $filters['date_from'] ?? '' }}" class="w-full rounded-lg border-2 border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-secondary/70 dark:text-light/70">Au</label>
                        <input type="date" name="date_to" value="{{ $filters['date_to'] ?? '' }}" class="w-full rounded-lg border-2 border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium text-secondary/70 dark:text-light/70">Statut</label>
                        <input type="text" name="status" value="{{ $filters['status'] ?? '' }}" placeholder="pending, confirmed..." class="w-full rounded-lg border-2 border-slate-200 px-3 py-2 text-sm dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                    </div>
                    <div class="flex items-end gap-2">
                        <button type="submit" class="flex-1 rounded-lg bg-primary px-4 py-2 text-sm font-semibold text-white">Filtrer</button>
                        @if (array_filter($filters))
                            <a href="{{ route('admin.transactions') }}" class="rounded-lg border border-slate-300 px-3 py-2 text-sm text-secondary dark:border-slate-700 dark:text-light">Réinitialiser</a>
                        @endif
                    </div>
                </form>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-4 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-secondary dark:text-light">Commandes (numéros)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-secondary/50 dark:text-light/50">
                                <th class="px-4 py-3 font-medium">Date</th>
                                <th class="px-4 py-3 font-medium">Utilisateur</th>
                                <th class="px-4 py-3 font-medium">Service</th>
                                <th class="px-4 py-3 font-medium">Pays</th>
                                <th class="px-4 py-3 font-medium">Numéro</th>
                                <th class="px-4 py-3 font-medium">Prix</th>
                                <th class="px-4 py-3 font-medium">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($orders as $order)
                                <tr>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ $order->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-secondary/80 dark:text-light/80">{{ $order->user->email ?? '—' }}</td>
                                    <td class="px-4 py-3 capitalize text-secondary/80 dark:text-light/80">{{ $order->service }}</td>
                                    <td class="px-4 py-3 capitalize text-secondary/80 dark:text-light/80">{{ str_replace('_', ' ', $order->country) }}</td>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ $order->phone }}</td>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ number_format($order->price_fcfa, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">{{ ucfirst($order->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="7" class="px-4 py-8 text-center text-sm text-secondary/60 dark:text-light/60">Aucune commande trouvée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $orders->links() }}</div>
            </div>

            <div class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <div class="border-b border-slate-100 p-4 dark:border-slate-800">
                    <h3 class="text-sm font-semibold text-secondary dark:text-light">Recharges (Mobile Money / FedaPay)</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="text-xs uppercase text-secondary/50 dark:text-light/50">
                                <th class="px-4 py-3 font-medium">Date</th>
                                <th class="px-4 py-3 font-medium">Utilisateur</th>
                                <th class="px-4 py-3 font-medium">Opérateur</th>
                                <th class="px-4 py-3 font-medium">Montant</th>
                                <th class="px-4 py-3 font-medium">Statut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse ($topups as $topup)
                                <tr>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ $topup->created_at->format('d/m/Y H:i') }}</td>
                                    <td class="px-4 py-3 text-secondary/80 dark:text-light/80">{{ $topup->user->email ?? '—' }}</td>
                                    <td class="px-4 py-3 text-secondary/80 dark:text-light/80">{{ $topup->operator ?? '—' }}</td>
                                    <td class="px-4 py-3 text-secondary dark:text-light">{{ number_format($topup->amount_fcfa, 0, ',', ' ') }} FCFA</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full bg-primary/10 px-2.5 py-0.5 text-xs font-medium text-primary">{{ ucfirst($topup->status) }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="5" class="px-4 py-8 text-center text-sm text-secondary/60 dark:text-light/60">Aucune recharge trouvée.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="border-t border-slate-100 p-4 dark:border-slate-800">{{ $topups->links() }}</div>
            </div>
        </div>
    </div>
</x-app-layout>
