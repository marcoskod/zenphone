<div
    x-show="showSuccessModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/60 p-4"
>
    <div class="w-full max-w-sm rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-slate-900">
        <div class="mx-auto flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-success to-green-400">
            <i class="fa-solid fa-check text-2xl text-white"></i>
        </div>

        <h3 class="mt-3.5 text-lg font-bold text-secondary dark:text-light">Code reçu !</h3>

        <div class="mt-4 rounded-lg border border-success/30 bg-success/5 p-4">
            <p class="text-xs text-secondary/60 dark:text-light/60">Votre code de vérification</p>
            <div class="mt-1.5 flex items-center justify-center gap-2">
                <p class="text-3xl font-bold tracking-widest text-secondary dark:text-light" x-text="smsCode"></p>
                <button @click="copySms()" type="button" aria-label="Copier le code" class="text-secondary/50 hover:text-primary">
                    <i class="fa-solid fa-check text-success" x-show="copiedSms" x-cloak></i>
                    <i class="fa-solid fa-copy" x-show="!copiedSms"></i>
                </button>
            </div>
        </div>

        <p class="mt-4 text-xs text-secondary/50 dark:text-light/50">
            Ce code reste consultable dans votre historique depuis le compte
            <span class="font-medium text-secondary dark:text-light" x-text="user?.email"></span>.
        </p>

        <div class="mt-5 flex gap-2.5">
            <button
                @click="startNewPurchase()"
                type="button"
                class="flex-1 rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white"
            >
                Nouvel achat
            </button>
            <button
                @click="showSuccessModal = false"
                type="button"
                class="flex-1 rounded-lg border border-slate-300 px-4 py-2.5 text-sm font-medium text-secondary dark:border-slate-700 dark:text-light"
            >
                Fermer
            </button>
        </div>
    </div>
</div>
