<div
    x-show="showAccountModal"
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
    aria-label="Mon compte"
>
    <div
        x-show="showAccountModal"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="flex max-h-[88dvh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900"
    >
        <div class="relative shrink-0 bg-gradient-to-br from-secondary to-primary p-5">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-accent">Zen_Sms &rsaquo; Mon compte</p>
            <h3 class="mt-1 pr-8 text-base font-bold text-white" x-text="user?.email"></h3>
            <button
                @click="showAccountModal = false"
                type="button"
                aria-label="Fermer"
                class="absolute right-4 top-4 flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
            >
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto p-5">
            <template x-if="dashboardLoading">
                <div class="flex items-center justify-center py-10 text-secondary/50">
                    <i class="fa-solid fa-circle-notch fa-spin"></i>
                </div>
            </template>

            <template x-if="!dashboardLoading && dashboard">
                <div class="space-y-4">
                    <div class="rounded-xl border border-slate-200 bg-light p-4 dark:border-slate-700 dark:bg-secondary/40">
                        <p class="text-xs text-secondary/60 dark:text-light/60">Crédit disponible</p>
                        <p class="mt-1 text-2xl font-bold text-secondary dark:text-light">
                            <span x-text="new Intl.NumberFormat('fr-FR').format(dashboard.stats.balance)"></span> FCFA
                        </p>
                        <p class="mt-1 text-[11px] text-secondary/50 dark:text-light/50" x-show="dashboard.stats.balance > 0">
                            Issu de remboursements — utilisé automatiquement à votre prochain achat.
                        </p>
                    </div>

                    <div class="grid grid-cols-2 gap-2.5">
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-lg font-bold text-secondary dark:text-light" x-text="dashboard.stats.numbers_bought"></p>
                            <p class="text-[11px] text-secondary/60 dark:text-light/60">Numéros achetés</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-lg font-bold text-secondary dark:text-light" x-text="dashboard.stats.sms_received"></p>
                            <p class="text-[11px] text-secondary/60 dark:text-light/60">SMS reçus</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <p class="text-lg font-bold text-secondary dark:text-light">
                                <span x-text="new Intl.NumberFormat('fr-FR').format(dashboard.stats.total_spent)"></span>
                            </p>
                            <p class="text-[11px] text-secondary/60 dark:text-light/60">Dépense totale (FCFA)</p>
                        </div>
                        <div class="rounded-lg border border-slate-200 p-3 dark:border-slate-700">
                            <a href="{{ route('history') }}" class="flex h-full flex-col items-center justify-center gap-1 text-center text-primary">
                                <i class="fa-solid fa-clock-rotate-left"></i>
                                <span class="text-[11px] font-medium">Historique complet</span>
                            </a>
                        </div>
                    </div>

                    <div>
                        <p class="mb-2 text-xs font-semibold text-secondary dark:text-light">Dernières commandes</p>
                        <div class="space-y-1.5">
                            <template x-for="order in dashboard.recent_orders" :key="order.id">
                                <div class="flex items-center justify-between rounded-lg border border-slate-100 px-3 py-2 text-xs dark:border-slate-800">
                                    <span class="font-medium capitalize text-secondary dark:text-light" x-text="order.service"></span>
                                    <span class="text-secondary/60 dark:text-light/60" x-text="order.phone"></span>
                                    <span :class="order.sms_received ? 'text-success' : 'text-secondary/40'">
                                        <i class="fa-solid" :class="order.sms_received ? 'fa-check' : 'fa-xmark'"></i>
                                    </span>
                                </div>
                            </template>

                            <p class="py-4 text-center text-xs text-secondary/50" x-show="dashboard.recent_orders.length === 0">
                                Aucune commande pour le moment.
                            </p>
                        </div>
                    </div>
                </div>
            </template>
        </div>
    </div>
</div>
