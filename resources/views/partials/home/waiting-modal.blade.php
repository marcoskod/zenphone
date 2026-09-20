<div
    x-show="showWaitingModal"
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
    aria-label="En attente du code SMS"
>
    <div
        x-show="showWaitingModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="max-h-[92dvh] w-full max-w-sm overflow-y-auto rounded-2xl bg-white p-6 text-center shadow-2xl dark:bg-slate-900"
    >
        <p class="text-xs font-medium text-secondary/50 dark:text-light/50" x-show="order">
            Commande <span x-text="'#' + order?.order_id"></span>
        </p>

        {{-- Radar / signal-wave animation around a phone icon --}}
        <div class="relative mx-auto mt-4 flex h-36 w-36 shrink-0 items-center justify-center">
            <span class="radar-ring" style="animation-delay: 0s"></span>
            <span class="radar-ring" style="animation-delay: 0.8s"></span>
            <span class="radar-ring" style="animation-delay: 1.6s"></span>

            <svg class="absolute h-36 w-36 -rotate-90" viewBox="0 0 144 144">
                <circle cx="72" cy="72" r="64" stroke-width="6" fill="none" class="stroke-slate-200 dark:stroke-slate-700" />
                <circle
                    cx="72" cy="72" r="64" stroke-width="6" fill="none" stroke-linecap="round"
                    stroke-dasharray="402.12"
                    :stroke-dashoffset="402.12 - (402.12 * waitingProgressPercent / 100)"
                    :class="secondsRemaining > 0 && secondsRemaining <= 30 ? 'stroke-error' : 'stroke-accent'"
                    class="transition-all duration-1000 ease-linear"
                />
            </svg>

            <div class="relative z-10 flex h-16 w-16 items-center justify-center rounded-full bg-gradient-to-br from-primary to-secondary text-white shadow-lg">
                <i class="fa-solid fa-mobile-screen text-2xl" aria-hidden="true"></i>
            </div>
        </div>

        <p class="mt-4 text-2xl font-bold tabular-nums text-secondary dark:text-light" x-text="`${waitingMinutes}:${waitingSeconds}`"></p>
        <p class="text-xs text-secondary/50 dark:text-light/50" x-show="!waitingTimedOut">En attente du SMS...</p>
        <p class="text-xs font-medium text-error" x-show="waitingTimedOut" x-cloak>Expiré</p>

        <div class="mt-4 flex min-w-0 items-center justify-center gap-2 rounded-lg bg-light px-3.5 py-2.5 dark:bg-secondary/40">
            <span class="min-w-0 break-all font-mono text-sm font-semibold text-secondary dark:text-light" x-text="order?.phone"></span>
            <button
                @click="copyPhone()"
                type="button"
                aria-label="Copier le numéro"
                class="shrink-0 p-1.5 text-secondary/50 transition hover:text-primary"
            >
                <i class="fa-solid fa-check text-success" x-show="copiedPhone" x-cloak aria-hidden="true"></i>
                <i class="fa-solid fa-copy" x-show="!copiedPhone" aria-hidden="true"></i>
            </button>
        </div>

        {{-- Timeout state --}}
        <div x-show="waitingTimedOut" x-cloak x-transition class="mt-4 rounded-lg border border-error/30 bg-error/5 p-3.5 text-left text-xs text-error">
            <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
            SMS non reçu avant l'expiration du numéro. Annulez pour être remboursé, ou essayez un autre pays.
        </div>

        <div class="mt-5 flex flex-wrap justify-center gap-2.5">
            <button
                @click="cancelWaitingOrder()"
                type="button"
                :disabled="cancelling"
                class="inline-flex min-h-[42px] items-center gap-2 rounded-full border border-error/30 px-4 py-2.5 text-xs font-medium text-error transition hover:bg-error/5 disabled:cursor-not-allowed disabled:opacity-50"
            >
                <i class="fa-solid fa-xmark" aria-hidden="true"></i>
                <span x-text="cancelling ? 'Annulation...' : 'Annuler'"></span>
            </button>
            <button
                @click="retryOtherCountry()"
                type="button"
                class="inline-flex min-h-[42px] items-center gap-2 rounded-full border border-slate-300 px-4 py-2.5 text-xs font-medium text-secondary transition hover:bg-slate-50 dark:border-slate-700 dark:text-light dark:hover:bg-slate-800"
            >
                <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                Autre pays
            </button>
        </div>
    </div>
</div>

<style>
    .radar-ring {
        position: absolute;
        inset: 0;
        border-radius: 9999px;
        border: 2px solid #D4A017;
        opacity: 0;
        animation: zen-radar-pulse 2.4s ease-out infinite;
    }

    @keyframes zen-radar-pulse {
        0% {
            transform: scale(0.55);
            opacity: 0.55;
        }
        100% {
            transform: scale(1);
            opacity: 0;
        }
    }

    /* Respect the OS-level "reduce motion" preference instead of forcing the pulse loop
       on everyone - a WCAG/HIG expectation, not just a nice-to-have. */
    @media (prefers-reduced-motion: reduce) {
        .radar-ring {
            animation: none;
            opacity: 0;
        }
    }
</style>
