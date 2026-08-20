<div
    x-show="showTopupModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/60 p-4"
>
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">
        <div class="flex items-center justify-between">
            <h3 class="text-lg font-bold text-secondary dark:text-light">Recharger mon solde</h3>
            <button @click="showTopupModal = false" type="button" aria-label="Fermer" class="text-secondary/50 hover:text-primary">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <p class="mt-1 text-xs text-secondary/60 dark:text-light/60" x-show="purchaseError || true">
            Solde insuffisant pour cet achat. Rechargez via FedaPay pour continuer.
        </p>

        <div class="mt-4 grid grid-cols-2 gap-2.5">
            <template x-for="amount in topupPresets" :key="amount">
                <button
                    type="button"
                    @click="topupAmount = amount"
                    class="rounded-lg border-2 px-3 py-2.5 text-sm font-semibold transition"
                    :class="topupAmount === amount ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-secondary dark:border-slate-700 dark:text-light'"
                >
                    <span x-text="new Intl.NumberFormat('fr-FR').format(amount)"></span> FCFA
                </button>
            </template>
        </div>

        <div class="mt-3">
            <label class="mb-1.5 block text-xs font-medium text-secondary/70 dark:text-light/70">Ou montant libre (min. 500 FCFA)</label>
            <input
                type="number"
                min="500"
                step="100"
                x-model.number="topupAmount"
                class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
            >
        </div>

        <p x-show="topupError" x-cloak class="mt-3 text-xs font-medium text-error" x-text="topupError"></p>

        <button
            @click="payTopup()"
            type="button"
            :disabled="topupLoading || topupAmount < 500"
            class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-3 text-sm font-bold text-white shadow-lg transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
        >
            <i class="fa-solid fa-credit-card"></i>
            <span x-show="!topupLoading">Payer via FedaPay</span>
            <span x-show="topupLoading" x-cloak>Traitement...</span>
        </button>

        <p class="mt-3 text-center text-[11px] text-secondary/40 dark:text-light/40">
            Orange Money · MTN MoMo · Moov Money · Carte bancaire
        </p>
    </div>
</div>
