{{-- Country modal: browse the full live country list without opening the order-form dropdown --}}
<div
    x-show="showCountriesModal"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/60 p-4"
>
    <div class="flex max-h-[88vh] w-full max-w-md flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
        <div class="relative shrink-0 bg-gradient-to-br from-secondary to-primary p-5">
            <p class="text-[10px] font-semibold uppercase tracking-widest text-accent">Zen_Sms &rsaquo; Couverture</p>
            <h3 class="mt-1 pr-8 text-base font-bold text-white">Tous les pays disponibles</h3>
            <p class="mt-1 text-xs text-white/50">Choisissez un pays pour démarrer votre achat.</p>
            <button
                @click="showCountriesModal = false"
                type="button"
                aria-label="Fermer"
                class="absolute right-4 top-4 flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
            >
                <i class="fa-solid fa-xmark text-xs"></i>
            </button>
        </div>

        <div class="border-b border-slate-100 p-3 dark:border-slate-800">
            <div class="flex items-center gap-2 rounded-md bg-slate-50 px-2.5 py-1.5 dark:bg-slate-800">
                <i class="fa-solid fa-magnifying-glass text-xs text-secondary/40"></i>
                <input
                    type="text"
                    x-model="countryFilter"
                    placeholder="Rechercher un pays..."
                    class="w-full border-none bg-transparent p-0 text-sm text-secondary focus:ring-0 dark:text-light"
                >
            </div>
        </div>

        <div class="flex-1 overflow-y-auto">
            <template x-for="item in filteredCountries" :key="item.code">
                <button
                    type="button"
                    @click="selectCountry(item.code); showCountriesModal = false"
                    class="flex w-full items-center gap-3 border-b border-slate-50 px-5 py-3 text-left transition hover:bg-slate-50 dark:border-slate-800 dark:hover:bg-slate-800"
                >
                    <span class="text-xl" x-text="countryFlag(item.code)"></span>
                    <span class="text-sm font-medium text-secondary dark:text-light" x-text="item.name"></span>
                </button>
            </template>

            <p class="px-5 py-8 text-center text-sm text-secondary/50" x-show="filteredCountries.length === 0">
                Aucun pays trouvé.
            </p>
        </div>

        <div class="shrink-0 border-t border-slate-100 bg-light p-4 dark:border-slate-800 dark:bg-secondary/40">
            <button
                @click="showCountriesModal = false"
                type="button"
                class="w-full rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white"
            >
                Fermer
            </button>
        </div>
    </div>
</div>
