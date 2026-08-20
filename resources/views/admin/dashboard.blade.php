<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Administration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('admin.partials.nav')

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <i class="fa-solid fa-users text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-secondary dark:text-light">{{ number_format($stats['active_users'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-secondary/60 dark:text-light/60">Utilisateurs actifs (30j)</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent">
                        <i class="fa-solid fa-sack-dollar text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-secondary dark:text-light">{{ number_format($stats['total_revenue'], 0, ',', ' ') }} FCFA</p>
                        <p class="text-xs text-secondary/60 dark:text-light/60">Chiffre d'affaires total</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <i class="fa-solid fa-receipt text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-secondary dark:text-light">{{ number_format($stats['transaction_count'], 0, ',', ' ') }}</p>
                        <p class="text-xs text-secondary/60 dark:text-light/60">Transactions (commandes + recharges)</p>
                    </div>
                </div>

                <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-accent/10 text-accent">
                        <i class="fa-solid fa-percent text-xl"></i>
                    </div>
                    <div>
                        <p class="text-xl font-bold text-secondary dark:text-light">{{ number_format($stats['average_margin_percent'], 1) }}%</p>
                        <p class="text-xs text-secondary/60 dark:text-light/60">Marge configurée</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
