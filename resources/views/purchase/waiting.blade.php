<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Numéro en attente de SMS') }}
        </h2>
    </x-slot>

    <div
        class="py-12"
        x-data="orderWaiting('{{ $order->expires_at?->toIso8601String() }}')"
        x-init="init()"
    >
        <div class="mx-auto max-w-2xl space-y-6 px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col items-center gap-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:justify-between">
                <div>
                    <p class="text-sm text-secondary/60 dark:text-light/60">Commande #{{ $order->id }}</p>
                    <div class="mt-2 flex items-center gap-2">
                        <p class="text-2xl font-bold text-secondary dark:text-light">{{ $order->phone }}</p>
                        <button
                            type="button"
                            @click="copyPhone('{{ $order->phone }}')"
                            aria-label="Copier le numéro"
                            class="flex h-8 w-8 items-center justify-center rounded-lg text-secondary/60 transition hover:bg-slate-100 hover:text-primary dark:text-light/60 dark:hover:bg-slate-800"
                        >
                            <i class="fa-solid fa-check text-success" x-show="copiedPhone" x-cloak></i>
                            <i class="fa-solid fa-copy" x-show="!copiedPhone"></i>
                        </button>
                    </div>
                    <p class="mt-1 text-sm text-secondary/70 dark:text-light/70">
                        Service : <span class="capitalize">{{ $order->service }}</span>
                        — Pays : <span class="capitalize">{{ str_replace('_', ' ', $order->country) }}</span>
                    </p>
                </div>

                <x-purchase.countdown-timer />
            </div>
        </div>
    </div>
</x-app-layout>
