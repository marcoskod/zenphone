<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-semibold leading-tight text-gray-800">
            {{ __('Administration') }}
        </h2>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            @include('admin.partials.nav')

            @if (session('status'))
                <div class="rounded-lg border border-success/30 bg-success/5 p-3.5 text-sm text-success">
                    {{ session('status') }}
                </div>
            @endif

            <div class="max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h3 class="text-base font-bold text-secondary dark:text-light">Marge globale</h3>
                <p class="mt-1 text-xs text-secondary/60 dark:text-light/60">
                    Appliquée à chaque prix fournisseur converti en FCFA. Valeur actuelle :
                    <strong class="text-secondary dark:text-light">{{ number_format($currentMargin, 1) }}%</strong>.
                </p>

                @if ((float) $currentMargin !== $envMargin)
                    <p class="mt-1 text-[11px] text-accent">
                        Une valeur différente de {{ number_format($envMargin, 1) }}% est définie dans .env
                        (SMSPOOL_MARGIN_PERCENT) mais est actuellement remplacée par ce réglage.
                    </p>
                @endif

                <form method="POST" action="{{ route('admin.settings.margin.update') }}" class="mt-4 flex items-end gap-3">
                    @csrf
                    <div class="flex-1">
                        <label for="margin_percent" class="mb-1.5 block text-xs font-medium text-secondary dark:text-light">Marge (%)</label>
                        <input
                            type="number"
                            id="margin_percent"
                            name="margin_percent"
                            step="0.1"
                            min="0"
                            max="500"
                            value="{{ old('margin_percent', $currentMargin) }}"
                            class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
                        >
                        @error('margin_percent')
                            <p class="mt-1 text-xs text-error">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white">
                        Enregistrer
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
