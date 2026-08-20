{{--
    Service/country lists below are illustrative, same convention as the landing page's
    pricing preview (Phase 2) - real options come from FiveSimService::getProducts()/
    getCountries() once action_05 builds the purchase page these fields redirect to.
--}}
<div class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <h3 class="text-sm font-semibold text-secondary dark:text-light">Achat rapide</h3>
    <p class="mt-1 text-xs text-secondary/60 dark:text-light/60">
        Choisissez un service et un pays pour démarrer un achat.
    </p>

    <form method="GET" action="{{ route('purchase') }}" class="mt-4 space-y-3">
        <div>
            <label for="quick-buy-service" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Service</label>
            <select id="quick-buy-service" name="service" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                <option value="whatsapp">WhatsApp</option>
                <option value="google">Google</option>
                <option value="instagram">Instagram</option>
                <option value="telegram">Telegram</option>
                <option value="tiktok">TikTok</option>
            </select>
        </div>

        <div>
            <label for="quick-buy-country" class="block text-xs font-medium text-secondary/70 dark:text-light/70">Pays</label>
            <select id="quick-buy-country" name="country" class="mt-1 block w-full rounded-xl border-slate-300 text-sm text-secondary shadow-sm focus:border-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800 dark:text-light">
                <option value="russia">Russie</option>
                <option value="ivory_coast">Côte d'Ivoire</option>
                <option value="senegal">Sénégal</option>
                <option value="usa">États-Unis</option>
            </select>
        </div>

        <button type="submit" class="w-full rounded-full bg-gradient-to-r from-primary to-accent px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
            Acheter un numéro
        </button>
    </form>
</div>
