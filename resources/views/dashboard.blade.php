<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                {{ __('Tableau de bord') }}
            </h2>

            <x-dashboard.notifications-dropdown />
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-dashboard.balance-header />

            <x-dashboard.stats-cards :stats="$stats" />

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="lg:col-span-2">
                    <x-dashboard.activity-chart :chart-data="$chartData" />
                </div>

                <x-dashboard.quick-buy />
            </div>

            <x-dashboard.recent-transactions :orders="$recentOrders" />
        </div>
    </div>
</x-app-layout>
