<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Acheter un numéro') }}
        </h2>
    </x-slot>

    <div
        class="py-12"
        x-data="purchaseForm('{{ $prefilledService }}', '{{ $prefilledCountry }}')"
    >
        <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
            @if ($errors->any())
                <div class="rounded-2xl border border-error/30 bg-error/5 p-4 text-sm text-error">
                    <ul class="list-disc space-y-1 pl-4">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>

                    @if (session('insufficient_balance'))
                        <a href="{{ route('topup') }}" class="mt-2 inline-flex items-center gap-2 font-semibold text-error underline">
                            <i class="fa-solid fa-wallet"></i>
                            Recharger mon solde
                        </a>
                    @endif
                </div>
            @endif

            <x-purchase.country-selector />

            <x-purchase.service-selector />

            <div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <p class="text-sm text-secondary/60 dark:text-light/60">Prix estimé</p>
                <p class="mt-1 text-2xl font-bold text-secondary dark:text-light" x-text="priceLabel"></p>
            </div>

            <button
                type="button"
                :disabled="!service || !country || !priceFcfa"
                @click="showConfirm = true"
                class="w-full rounded-full bg-gradient-to-r from-primary to-accent px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
            >
                Acheter ce numéro
            </button>

            <form method="POST" action="{{ route('purchase.store') }}" x-ref="purchaseForm">
                @csrf
                <input type="hidden" name="service" :value="service">
                <input type="hidden" name="country" :value="country">
            </form>

            <x-purchase.confirm-modal />
        </div>
    </div>
</x-app-layout>
