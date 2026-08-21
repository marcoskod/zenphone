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

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-sm font-semibold text-secondary dark:text-light">Chiffre d'affaires et marge (30 derniers jours)</h3>
                <div class="mt-4 h-72">
                    <canvas id="admin-revenue-chart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const canvas = document.getElementById('admin-revenue-chart');

            if (!canvas || !window.Chart) {
                return;
            }

            new window.Chart(canvas, {
                type: 'bar',
                data: {
                    labels: @json($chartData['labels']),
                    datasets: [
                        {
                            type: 'line',
                            label: 'Chiffre d\'affaires (FCFA)',
                            data: @json($chartData['revenue']),
                            borderColor: '#1F3569',
                            backgroundColor: 'rgba(31, 53, 105, 0.1)',
                            tension: 0.3,
                            fill: true,
                            yAxisID: 'y',
                        },
                        {
                            type: 'bar',
                            label: 'Marge estimée (FCFA)',
                            data: @json($chartData['margin']),
                            backgroundColor: '#D4A017',
                            yAxisID: 'y',
                        },
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: { beginAtZero: true },
                    },
                },
            });
        });
    </script>
</x-app-layout>
