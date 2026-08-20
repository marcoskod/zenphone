<div
    x-show="showErrorModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/60 p-4"
>
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-slate-900">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-error to-red-400">
            <i class="fa-solid fa-xmark text-2xl text-white"></i>
        </div>

        <h3 class="mt-3.5 text-lg font-bold text-secondary dark:text-light">Une erreur est survenue</h3>
        <p class="mt-2 text-sm text-secondary/60 dark:text-light/60" x-text="errorMessage"></p>

        <div class="mt-5 flex gap-2.5">
            <button
                @click="showErrorModal = false"
                type="button"
                class="flex-1 rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white"
            >
                Réessayer
            </button>
            <button
                @click="showErrorModal = false"
                type="button"
                class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-secondary dark:border-slate-700 dark:text-light"
            >
                Fermer
            </button>
        </div>
    </div>
</div>
