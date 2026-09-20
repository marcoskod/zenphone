<div
    x-show="showErrorModal"
    x-cloak
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/60 p-4"
    role="dialog"
    aria-modal="true"
    aria-label="Erreur"
>
    <div
        x-show="showErrorModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="max-h-[92dvh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-slate-900"
    >
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-error to-red-400">
            <i class="fa-solid fa-xmark text-2xl text-white" aria-hidden="true"></i>
        </div>

        <h3 class="mt-3.5 text-lg font-bold text-secondary dark:text-light">Une erreur est survenue</h3>
        <p class="mt-2 break-words text-sm text-secondary/60 dark:text-light/60" x-text="errorMessage"></p>

        <div class="mt-5 flex flex-col gap-2.5 sm:flex-row">
            <button
                @click="showErrorModal = false"
                type="button"
                class="min-h-[44px] flex-1 rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
            >
                Réessayer
            </button>
            <button
                @click="showErrorModal = false"
                type="button"
                class="min-h-[44px] flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-secondary transition hover:bg-slate-50 dark:border-slate-700 dark:text-light dark:hover:bg-slate-800"
            >
                Fermer
            </button>
        </div>
    </div>
</div>
