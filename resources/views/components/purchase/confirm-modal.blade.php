<div
    x-show="showConfirm"
    x-cloak
    x-transition
    class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/50 p-4"
>
    <div
        @click.outside="showConfirm = false"
        class="w-full max-w-md rounded-2xl bg-white p-6 shadow-xl dark:bg-slate-900"
    >
        <h3 class="text-lg font-semibold text-secondary dark:text-light">Confirmer l'achat</h3>

        <dl class="mt-4 space-y-2 text-sm">
            <div class="flex justify-between">
                <dt class="text-secondary/60 dark:text-light/60">Service</dt>
                <dd class="font-medium capitalize text-secondary dark:text-light" x-text="selectedServiceLabel"></dd>
            </div>
            <div class="flex justify-between">
                <dt class="text-secondary/60 dark:text-light/60">Pays</dt>
                <dd class="font-medium text-secondary dark:text-light" x-text="selectedCountryLabel"></dd>
            </div>
            <div class="flex justify-between border-t border-slate-100 pt-2 dark:border-slate-800">
                <dt class="text-secondary/60 dark:text-light/60">Prix</dt>
                <dd class="font-bold text-secondary dark:text-light" x-text="priceLabel"></dd>
            </div>
        </dl>

        <div class="mt-6 flex gap-3">
            <button
                @click="showConfirm = false"
                type="button"
                class="flex-1 rounded-full border border-slate-300 px-4 py-2.5 text-sm font-medium text-secondary transition hover:bg-slate-50 dark:border-slate-700 dark:text-light dark:hover:bg-slate-800"
            >
                Annuler
            </button>
            <button
                @click="$refs.purchaseForm.submit()"
                type="button"
                class="flex-1 rounded-full bg-gradient-to-r from-primary to-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90"
            >
                Confirmer l'achat
            </button>
        </div>
    </div>
</div>
