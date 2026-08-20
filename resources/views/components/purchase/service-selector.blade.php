<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <label for="service-filter" class="block text-sm font-medium text-secondary dark:text-light">Service</label>

    <div class="relative mt-2">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-secondary/40"></i>
        <input
            id="service-filter"
            type="text"
            x-model="serviceFilter"
            placeholder="Rechercher un service..."
            class="w-full rounded-xl border-slate-300 pl-9 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>

    <div class="mt-4 grid grid-cols-2 gap-2 sm:grid-cols-3" x-show="country" x-cloak>
        <template x-for="item in filteredServices" :key="item.code">
            <button
                type="button"
                @click="selectService(item.code)"
                :class="service === item.code ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-secondary dark:border-slate-700 dark:text-light'"
                class="flex flex-col items-center gap-1 rounded-xl border p-3 text-center text-xs transition hover:border-primary"
            >
                <i :class="serviceIcon(item.code)" class="text-lg"></i>
                <span x-text="item.label" class="capitalize"></span>
            </button>
        </template>
    </div>

    <p class="mt-4 text-sm text-secondary/50 dark:text-light/50" x-show="!country">
        Sélectionnez d'abord un pays.
    </p>

    <p class="mt-4 text-sm text-secondary/50 dark:text-light/50" x-show="country && filteredServices.length === 0" x-cloak>
        Aucun service trouvé.
    </p>
</div>
