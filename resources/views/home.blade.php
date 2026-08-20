<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Zen_Sms — Numéros virtuels pour vos SMS de vérification</title>
        <meta name="description" content="Achetez un numéro virtuel et recevez votre code SMS en quelques secondes. Paiement en Mobile Money via FedaPay.">

        {{-- Set the dark class before first paint to avoid a flash of the wrong theme. --}}
        <script>
            if (localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }

            window.ZEN_SMS_CONFIG = {
                fedapayPublicKey: @js(config('fedapay.public_key')),
            };
        </script>

        <!-- Font Awesome (icons) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <!-- FedaPay checkout widget (client-side; server-side verification happens in TopupController) -->
        <script src="https://cdn.fedapay.com/checkout.js?v=1.1.7"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        x-data="zenSinglePage()"
        x-init="init()"
        class="min-h-screen bg-light font-sans text-secondary antialiased dark:bg-secondary dark:text-light"
    >
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-8 sm:py-12">

            {{-- ═══ CARD ═══ --}}
            <div class="w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl">

                {{-- ═══ TOP NAVY HERO ═══ --}}
                <div class="bg-gradient-to-br from-secondary via-primary to-secondary text-white">
                    <div class="h-1.5 bg-gradient-to-r from-accent via-yellow-300 to-accent"></div>

                    <div class="space-y-5 p-6 sm:p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-accent">
                                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                                Zen_Sms · SMS instantané
                            </div>

                            {{-- Account icon, visible once authenticated --}}
                            <button
                                x-show="authenticated"
                                x-cloak
                                @click="openAccount()"
                                type="button"
                                aria-label="Mon compte"
                                class="flex h-9 w-9 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                            >
                                <i class="fa-solid fa-user"></i>
                            </button>
                        </div>

                        <h1 class="text-2xl font-bold leading-tight sm:text-3xl" style="font-family: 'Inter', sans-serif;">
                            Recevez vos codes SMS
                            <span class="text-accent">sans carte SIM</span>
                        </h1>

                        <p class="text-sm text-white/70">
                            Un numéro virtuel, un code reçu en direct. Payez en Mobile Money via FedaPay.
                        </p>

                        {{-- Rotating showcase of supported services --}}
                        <div class="relative h-24 overflow-hidden rounded-xl bg-white/10">
                            <template x-for="(s, i) in showcaseServices" :key="s.code">
                                <div
                                    x-show="showcaseIndex === i"
                                    x-transition:enter="transition ease-out duration-500"
                                    x-transition:enter-start="opacity-0 translate-x-4"
                                    x-transition:enter-end="opacity-100 translate-x-0"
                                    x-transition:leave="transition ease-in duration-300"
                                    x-transition:leave-start="opacity-100"
                                    x-transition:leave-end="opacity-0"
                                    class="absolute inset-0 flex items-center gap-4 px-5"
                                >
                                    <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-white/15">
                                        <i :class="s.icon" class="text-2xl text-accent"></i>
                                    </div>
                                    <div>
                                        <div class="font-semibold text-white" x-text="s.label"></div>
                                        <div class="text-xs text-white/60">
                                            à partir de <span x-text="s.priceFrom"></span> FCFA
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div class="absolute bottom-2 left-0 right-0 flex items-center justify-center gap-1.5">
                                <template x-for="(s, i) in showcaseServices" :key="'dot-' + s.code">
                                    <span
                                        class="h-1.5 w-1.5 rounded-full transition"
                                        :class="showcaseIndex === i ? 'bg-accent w-4' : 'bg-white/30'"
                                    ></span>
                                </template>
                            </div>
                        </div>

                        <div>
                            <p class="mb-2 text-[10px] font-semibold uppercase tracking-widest text-white/40">
                                Plus de 150 pays disponibles
                            </p>
                            <div class="flex flex-wrap items-center gap-2">
                                <template x-for="code in ['ivorycoast', 'senegal', 'ghana', 'usa']" :key="code">
                                    <span class="flex items-center gap-1.5 rounded-full border border-white/15 bg-white/10 px-2.5 py-1 text-xs text-white/85">
                                        <span x-text="countryFlag(code)"></span>
                                        <span x-text="countries.find(c => c.code === code)?.name ?? code"></span>
                                    </span>
                                </template>

                                <button
                                    @click="showCountriesModal = true"
                                    type="button"
                                    class="inline-flex items-center gap-1.5 rounded-full border border-white/25 bg-white/10 px-2.5 py-1 text-xs font-semibold text-white/90 transition hover:bg-white/20"
                                >
                                    <i class="fa-solid fa-globe text-[11px]"></i>
                                    Voir tous les pays
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ═══ BOTTOM WHITE ORDER FORM ═══ --}}
                <div class="bg-white dark:bg-slate-900">
                    <div class="h-1 bg-gradient-to-r from-primary to-secondary"></div>

                    <div class="space-y-4 p-6 sm:p-8">
                        <div class="flex items-center gap-2.5 rounded-lg border border-green-200 bg-green-50 px-3.5 py-2.5 dark:border-green-900 dark:bg-green-950/30">
                            <i class="fa-solid fa-shield-halved text-success"></i>
                            <span class="text-xs text-green-700 dark:text-green-400">
                                Paiement 100&nbsp;% sécurisé via <strong>FedaPay</strong> · Mobile Money &amp; Carte bancaire
                            </span>
                        </div>

                        <div>
                            <h2 class="text-base font-bold text-secondary dark:text-light">Achetez votre numéro</h2>
                            <p class="text-xs text-secondary/60 dark:text-light/60">Choisissez un pays et un service pour commencer.</p>
                        </div>

                        @include('partials.home.order-form')
                    </div>
                </div>
            </div>

            @include('partials.home.link-bar')
        </div>

        @include('partials.home.countries-modal')
        @include('partials.home.waiting-modal')
        @include('partials.home.success-modal')
        @include('partials.home.error-modal')
        @include('partials.home.topup-modal')
        @include('partials.home.account-modal')
        @include('partials.home.legal-modals')
    </body>
</html>
