{{-- Country selector --}}
<div>
    <label class="mb-1.5 block text-xs font-semibold text-secondary dark:text-light">Pays</label>
    <div class="relative">
        <button
            type="button"
            @click="showCountryDropdown = !showCountryDropdown"
            class="flex w-full items-center justify-between rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-left text-sm text-secondary transition focus:border-primary dark:border-slate-700 dark:text-light"
        >
            <span class="flex items-center gap-2">
                <span x-show="country" class="relative flex h-[18px] w-6 shrink-0 items-center justify-center overflow-hidden rounded-[3px] bg-slate-200 text-[9px] font-bold text-slate-500 ring-1 ring-black/10 dark:bg-slate-700">
                    <span x-text="countries.find(c => c.code === country)?.iso"></span>
                    <img x-show="countryFlagUrl(country)" :src="countryFlagUrl(country, 80)" alt="" width="24" height="18" class="absolute inset-0 h-full w-full object-cover" x-on:error="$el.remove()">
                </span>
                <span x-text="selectedCountryLabel"></span>
            </span>
            <i class="fa-solid fa-chevron-down text-xs text-secondary/40 transition" :class="{ 'rotate-180': showCountryDropdown }"></i>
        </button>

        <div
            x-show="showCountryDropdown"
            x-cloak
            x-transition
            @click.outside="showCountryDropdown = false"
            class="absolute z-30 mt-1.5 w-full overflow-hidden rounded-lg border-2 border-slate-200 bg-white shadow-xl dark:border-slate-700 dark:bg-slate-800"
        >
            <div class="border-b border-slate-100 p-2 dark:border-slate-700">
                <div class="flex items-center gap-2 rounded-md bg-slate-50 px-2.5 py-1.5 dark:bg-slate-900">
                    <i class="fa-solid fa-magnifying-glass text-xs text-secondary/40"></i>
                    <input
                        type="text"
                        x-model="countryFilter"
                        placeholder="Rechercher un pays..."
                        class="w-full border-none bg-transparent p-0 text-sm text-secondary focus:ring-0 dark:text-light"
                    >
                </div>
            </div>
            <div class="max-h-56 overflow-y-auto">
                <template x-for="item in filteredCountries" :key="item.code">
                    <button
                        type="button"
                        @click="selectCountry(item.code)"
                        class="flex w-full items-center gap-3 px-3.5 py-2 text-left text-sm transition hover:bg-slate-50 dark:hover:bg-slate-700"
                        :class="country === item.code ? 'bg-primary/10 text-primary' : 'text-secondary dark:text-light'"
                    >
                        <span class="relative flex h-[18px] w-6 shrink-0 items-center justify-center overflow-hidden rounded-[3px] bg-slate-200 text-[9px] font-bold text-slate-500 ring-1 ring-black/10 dark:bg-slate-700">
                            <span x-text="item.iso"></span>
                            <img :src="countryFlagUrl(item.code, 80)" x-show="countryFlagUrl(item.code)" alt="" width="24" height="18" loading="lazy" class="absolute inset-0 h-full w-full object-cover" x-on:error="$el.remove()">
                        </span>
                        <span class="min-w-0 flex-1 truncate" x-text="item.name"></span>
                        <span class="shrink-0 text-[11px] text-secondary/50 dark:text-light/50" x-show="item.from_price_fcfa">dès <span class="font-bold text-accent" x-text="formatFcfa(item.from_price_fcfa) + ' F'"></span></span>
                    </button>
                </template>
                <p class="px-3.5 py-3 text-sm text-secondary/50" x-show="filteredCountries.length === 0">Aucun pays trouvé.</p>
            </div>
        </div>
    </div>
</div>

{{-- Service selector --}}
<div x-show="country" x-cloak>
    <label class="mb-1.5 block text-xs font-semibold text-secondary dark:text-light">Service</label>
    <div class="relative mb-2">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-xs text-secondary/40"></i>
        <input
            type="text"
            x-model="serviceFilter"
            placeholder="Rechercher un service..."
            class="w-full rounded-lg border-2 border-slate-200 py-2.5 pl-9 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>
    {{-- Loading skeleton - shown the instant a country is picked, so the grid below
         never flashes an empty "Aucun service trouvé" while the AJAX call is in flight. --}}
    <div class="grid grid-cols-3 gap-2" x-show="loadingServices" x-cloak>
        <template x-for="n in 9" :key="n">
            <div class="flex min-h-[72px] animate-pulse flex-col items-center justify-center gap-2 rounded-lg border-2 border-slate-200 p-2.5 dark:border-slate-700">
                <div class="h-5 w-5 rounded-full bg-slate-200 dark:bg-slate-700"></div>
                <div class="h-2 w-10 rounded bg-slate-200 dark:bg-slate-700"></div>
            </div>
        </template>
    </div>

    <div class="grid grid-cols-3 gap-2" x-show="!loadingServices" x-cloak>
        <template x-for="item in filteredServices" :key="item.code">
            <button
                type="button"
                @click="selectService(item.code)"
                class="flex min-h-[88px] flex-col items-center justify-center gap-1 rounded-lg border-2 p-2.5 text-center transition"
                :class="service === item.code ? 'border-primary bg-primary/10 text-primary' : 'border-slate-200 text-secondary hover:border-primary/40 dark:border-slate-700 dark:text-light'"
            >
                <span class="flex h-8 w-8 items-center justify-center rounded-lg text-base shadow-sm" :style="serviceTileStyle(item.code)">
                    <i :class="serviceIcon(item.code)"></i>
                </span>
                <span class="w-full truncate text-[11px]" x-text="item.label"></span>
                <span class="text-[10px] font-semibold text-accent" x-text="'dès ' + new Intl.NumberFormat('fr-FR').format(item.price_fcfa) + ' F'"></span>
            </button>
        </template>
    </div>
    <p class="mt-3 text-center text-sm text-secondary/50" x-show="!loadingServices && filteredServices.length === 0" x-cloak>Aucun service trouvé.</p>

    {{-- Only the ~10 most requested services show by default; the rest of the 150+
         catalog is one tap away instead of forcing everyone to scroll past it. --}}
    <button
        type="button"
        x-show="!loadingServices && !serviceFilter && !showAllServices && services.length > popularServices.length"
        x-cloak
        @click="showAllServices = true"
        class="mt-3 flex w-full items-center justify-center gap-1.5 text-xs font-semibold text-primary hover:underline"
    >
        <span>Voir tous les services</span>
        <span class="text-secondary/40 dark:text-light/40" x-text="'(' + services.length + ')'"></span>
    </button>
</div>

{{-- Order summary --}}
<div x-show="service && country" x-cloak class="rounded-lg border border-slate-200 bg-light p-3.5 dark:border-slate-700 dark:bg-secondary/40">
    <div class="flex items-center justify-between text-sm">
        <span class="text-secondary/70 dark:text-light/70">Service</span>
        <span class="font-medium capitalize text-secondary dark:text-light" x-text="selectedServiceLabel"></span>
    </div>
    <div class="mt-1 flex items-center justify-between text-sm">
        <span class="text-secondary/70 dark:text-light/70">Pays</span>
        <span class="font-medium text-secondary dark:text-light" x-text="selectedCountryLabel"></span>
    </div>
    <div class="mt-2 flex items-center justify-between border-t border-slate-200 pt-2 dark:border-slate-700">
        <span class="text-sm font-semibold text-secondary dark:text-light">Total</span>
        <span class="h-6 w-20 animate-pulse rounded bg-slate-200 dark:bg-slate-700" x-show="loadingPrice" x-cloak></span>
        <span class="text-xl font-bold text-accent" x-show="!loadingPrice" x-text="priceLabel"></span>
    </div>
</div>

{{-- Inline auth (guests only) --}}
<div x-show="authChecked && !authenticated" x-cloak class="space-y-3 rounded-lg border border-slate-200 p-3.5 dark:border-slate-700">
    <p class="text-xs font-semibold text-secondary dark:text-light">Votre compte</p>
    <div>
        <input
            type="email"
            x-model="authEmail"
            placeholder="Adresse email"
            autocomplete="email"
            class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>
    <div>
        <input
            type="password"
            x-model="authPassword"
            placeholder="Mot de passe"
            autocomplete="current-password"
            class="w-full rounded-lg border-2 border-slate-200 px-3.5 py-2.5 text-sm text-secondary focus:border-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light"
        >
    </div>
    <p class="text-[11px] text-secondary/50 dark:text-light/50">
        Nouveau ? Votre compte est créé automatiquement. Déjà client ? Entrez votre mot de passe habituel.
    </p>
    <p x-show="authError" x-cloak class="text-xs font-medium text-error" x-text="authError"></p>
</div>

<p x-show="purchaseError" x-cloak class="text-xs font-medium text-error" x-text="purchaseError"></p>

{{-- Purchase button --}}
<button
    type="button"
    @click="purchase()"
    :disabled="!canPurchase"
    class="flex w-full items-center justify-center gap-2 rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-3.5 text-sm font-bold text-white shadow-lg transition hover:opacity-90 disabled:cursor-not-allowed disabled:opacity-40"
>
    <template x-if="!purchasing && !authLoading">
        <span class="flex items-center gap-2">
            <i class="fa-solid fa-bolt"></i>
            Acheter maintenant
        </span>
    </template>
    <template x-if="purchasing || authLoading">
        <span class="flex items-center gap-2">
            <i class="fa-solid fa-circle-notch fa-spin"></i>
            Un instant...
        </span>
    </template>
</button>

<div class="flex flex-wrap justify-center gap-x-4 gap-y-1 text-[11px] text-secondary/50 dark:text-light/50">
    <span><i class="fa-solid fa-check text-success"></i> Paiement sécurisé</span>
    <span><i class="fa-solid fa-check text-success"></i> Réception instantanée</span>
    <span><i class="fa-solid fa-check text-success"></i> Remboursé si aucun SMS</span>
</div>
