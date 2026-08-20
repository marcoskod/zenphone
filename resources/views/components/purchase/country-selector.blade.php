<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <label for="country-filter" class="block text-sm font-medium text-secondary dark:text-light">Pays</label>

    <div class="relative mt-2">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-secondary/40"></i>
        <input
            id="country-filter"
            type="text"
            x-model="countryFilter"
            placeholder="Rechercher un pays..."
            class="w-full rounded-xl border-slate-300 pl-9 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>

    <div class="mt-4 max-h-64 space-y-1 overflow-y-auto">
        <template x-for="item in filteredCountries" :key="item.code">
            <button
                type="button"
                @click="selectCountry(item.code)"
                :class="country === item.code ? 'bg-primary/10 text-primary' : 'text-secondary dark:text-light'"
                class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-left text-sm transition hover:bg-slate-50 dark:hover:bg-slate-800"
            >
                <span x-text="countryFlag(item.code)" class="text-lg"></span>
                <span x-text="item.name"></span>
            </button>
        </template>

        <p class="px-3 py-2 text-sm text-secondary/50 dark:text-light/50" x-show="filteredCountries.length === 0" x-cloak>
            Aucun pays trouvé.
        </p>
    </div>
</div>
