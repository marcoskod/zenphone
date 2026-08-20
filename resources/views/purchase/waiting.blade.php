<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Numéro en attente de SMS') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-secondary/60 dark:text-light/60">Commande #{{ $order->id }}</p>
                <p class="mt-2 text-2xl font-bold text-secondary dark:text-light">{{ $order->phone }}</p>
                <p class="mt-1 text-sm text-secondary/70 dark:text-light/70">
                    Service : <span class="capitalize">{{ $order->service }}</span>
                    — Pays : <span class="capitalize">{{ str_replace('_', ' ', $order->country) }}</span>
                    — Statut : {{ ucfirst($order->status) }}
                </p>

                <p class="mt-6 rounded-xl bg-primary/10 px-4 py-3 text-sm text-primary">
                    <i class="fa-solid fa-circle-info"></i>
                    L'écran d'attente avec compte à rebours et réception automatique du SMS arrive très prochainement.
                </p>

                <a href="{{ route('dashboard') }}" class="mt-6 inline-flex items-center gap-2 text-sm font-medium text-primary hover:text-primary/80">
                    <i class="fa-solid fa-arrow-left"></i>
                    Retour au tableau de bord
                </a>
            </div>
        </div>
    </div>
</x-app-layout>
