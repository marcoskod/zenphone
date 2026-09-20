<div
    x-show="showSuccessModal"
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
    aria-label="Code SMS reçu"
>
    <div
        x-show="showSuccessModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="max-h-[92dvh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-slate-900"
    >
        <div class="success-pop mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-success to-green-400">
            <i class="fa-solid fa-check text-2xl text-white" aria-hidden="true"></i>
        </div>

        <h3 class="mt-3.5 text-lg font-bold text-secondary dark:text-light">Code reçu !</h3>

        <div class="mt-4 rounded-lg border border-success/30 bg-success/5 p-4">
            <p class="text-xs text-secondary/60 dark:text-light/60">Votre code de vérification</p>
            <div class="mt-1.5 flex min-w-0 items-center justify-center gap-2">
                <p class="break-all text-3xl font-bold tracking-widest text-secondary dark:text-light" x-text="smsCode"></p>
                <button
                    @click="copySms()"
                    type="button"
                    aria-label="Copier le code"
                    class="shrink-0 p-1.5 text-secondary/50 transition hover:text-primary"
                >
                    <i class="fa-solid fa-check text-success" x-show="copiedSms" x-cloak aria-hidden="true"></i>
                    <i class="fa-solid fa-copy" x-show="!copiedSms" aria-hidden="true"></i>
                </button>
            </div>
        </div>

        <p class="mt-4 text-xs text-secondary/50 dark:text-light/50">
            Ce code reste consultable dans votre historique depuis le compte
            <span class="font-medium text-secondary dark:text-light" x-text="user?.email"></span>.
        </p>

        <div class="mt-5 flex flex-col gap-2.5 sm:flex-row">
            <button
                @click="startNewPurchase()"
                type="button"
                class="min-h-[44px] flex-1 rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white transition hover:opacity-90"
            >
                Nouvel achat
            </button>
            <button
                @click="showSuccessModal = false"
                type="button"
                class="min-h-[44px] flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-secondary transition hover:bg-slate-50 dark:border-slate-700 dark:text-light dark:hover:bg-slate-800"
            >
                Fermer
            </button>
        </div>
    </div>
</div>

<style>
    .success-pop {
        animation: zen-success-pop 400ms cubic-bezier(0.34, 1.56, 0.64, 1);
    }

    @keyframes zen-success-pop {
        0% {
            transform: scale(0.4);
            opacity: 0;
        }
        100% {
            transform: scale(1);
            opacity: 1;
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .success-pop {
            animation: none;
        }
    }
</style>
