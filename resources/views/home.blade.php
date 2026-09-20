<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Zenphone — Numéros virtuels pour vos SMS de vérification</title>
        <meta name="description" content="Créez vos comptes WhatsApp, Telegram, Google… avec un numéro étranger. Choisissez un pays, payez en Mobile Money et recevez votre code SMS en quelques secondes.">
        <meta name="theme-color" content="#1F3569">
        <link rel="canonical" href="{{ url('/') }}">
        <link rel="icon" href="{{ asset('favicon.ico') }}">

        {{-- Link previews (WhatsApp, Facebook, Telegram, X) - what people see when the ad or a shared link is posted. --}}
        <meta property="og:type" content="website">
        <meta property="og:site_name" content="{{ config('app.name') }}">
        <meta property="og:locale" content="fr_FR">
        <meta property="og:url" content="{{ url('/') }}">
        <meta property="og:title" content="Zenphone — Créez vos comptes avec un numéro étranger">
        <meta property="og:description" content="WhatsApp, Telegram, Google… Recevez votre code de vérification en quelques secondes. Payez en Mobile Money, remboursé si aucun SMS.">
        <meta name="twitter:card" content="summary">

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

        <!-- FedaPay checkout widget (client-side; server-side verification happens in PurchaseController::payConfirm and the FedaPay webhook) -->
        <script src="https://cdn.fedapay.com/checkout.js?v=1.1.7"></script>

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body
        x-data="zenSinglePage()"
        class="min-h-screen bg-light font-sans text-secondary antialiased dark:bg-secondary dark:text-light"
    >
        <div class="flex min-h-screen flex-col items-center px-4 py-6 sm:py-12">

            {{-- ═══ CARD ═══ --}}
            <div class="w-full max-w-lg overflow-hidden rounded-2xl shadow-2xl">

                {{-- ═══ TOP NAVY HERO ═══ --}}
                <div class="bg-gradient-to-br from-secondary via-primary to-secondary text-white">
                    <div class="h-1.5 bg-gradient-to-r from-accent via-yellow-300 to-accent"></div>

                    <div class="space-y-5 p-6 sm:p-8">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2 text-[11px] font-semibold uppercase tracking-[0.2em] text-accent">
                                <span class="h-1.5 w-1.5 rounded-full bg-accent"></span>
                                Zenphone · SMS instantané
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
                            Créez vos comptes avec un
                            <span class="text-accent">numéro étranger</span>
                            pour toutes vos activités en ligne
                        </h1>

                        <p class="text-sm text-white/70">
                            WhatsApp, Telegram, Google, TikTok… Choisissez un pays, payez en Mobile Money
                            et recevez votre code de vérification en quelques secondes. Sans carte SIM.
                        </p>

                        {{-- Rotating showcase: two services at a time, each with its real logo colours --}}
                        <div class="relative h-28 overflow-hidden rounded-xl bg-white/10" aria-live="off">
                            <template x-for="(pair, i) in showcasePairs" :key="'pair-' + i">
                                {{-- Pure CSS fade (no x-show/x-transition): an interrupted Alpine transition can
                                     leave slides stuck half-visible and stacked on top of each other. Outgoing
                                     fades first, incoming waits 300ms, so two slides never overlap. --}}
                                <div
                                    class="absolute inset-0 grid grid-cols-2 gap-2 px-3 pb-6 pt-3 motion-reduce:transition-none"
                                    :class="showcaseIndex === i
                                        ? 'opacity-100 transition-opacity duration-500 delay-300'
                                        : 'pointer-events-none opacity-0 transition-opacity duration-300'"
                                    :aria-hidden="showcaseIndex === i ? 'false' : 'true'"
                                >
                                    <template x-for="s in pair" :key="s.code">
                                        <div class="flex min-w-0 items-center gap-2 rounded-lg bg-white/10 px-2 sm:gap-2.5 sm:px-2.5">
                                            <span
                                                class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl text-lg shadow-md sm:h-10 sm:w-10 sm:text-xl"
                                                :style="serviceTileStyle(s.code)"
                                            >
                                                <i :class="serviceIcon(s.code)"></i>
                                            </span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-[13px] font-semibold text-white sm:text-sm" x-text="s.label"></span>
                                                <span class="block truncate text-[11px] text-white/60" x-show="s.priceFrom !== null">
                                                    dès <span x-text="new Intl.NumberFormat('fr-FR').format(s.priceFrom)"></span> F
                                                </span>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <div class="absolute bottom-2 left-0 right-0 flex items-center justify-center gap-1.5">
                                <template x-for="(pair, i) in showcasePairs" :key="'dot-' + i">
                                    <span
                                        class="h-1.5 rounded-full transition-all"
                                        :class="showcaseIndex === i ? 'w-4 bg-accent' : 'w-1.5 bg-white/30'"
                                    ></span>
                                </template>
                            </div>
                        </div>

                        {{-- Quick steps: what to do, at a glance --}}
                        @php
                            $heroSteps = [
                                ['n' => 1, 'label' => 'Choisissez un pays et une application'],
                                ['n' => 2, 'label' => 'Payez en Mobile Money'],
                                ['n' => 3, 'label' => 'Recevez votre code ici'],
                            ];
                        @endphp
                        <ol class="grid grid-cols-3 gap-2" aria-label="Les 3 étapes">
                            @foreach ($heroSteps as $step)
                                <li class="relative flex flex-col items-center text-center">
                                    @unless ($loop->last)
                                        <span class="absolute left-[calc(50%+22px)] right-[calc(-50%+22px)] top-4 border-t border-dashed border-white/25" aria-hidden="true"></span>
                                    @endunless
                                    <span class="relative z-10 flex h-8 w-8 items-center justify-center rounded-full bg-accent text-sm font-bold text-secondary shadow-md ring-4 ring-primary/60">{{ $step['n'] }}</span>
                                    <span class="mt-2 text-[11px] font-medium leading-snug text-white/85">{{ $step['label'] }}</span>
                                </li>
                            @endforeach
                        </ol>

                        {{-- Coverage: sentence + overlapping flag "avatars" (5 on phones, 7 / 8 as room allows) --}}
                        @php
                            $flags = [['bj', 'Bénin'], ['sn', 'Sénégal'], ['tg', 'Togo'], ['gh', 'Ghana'], ['ng', 'Nigeria'], ['cm', 'Cameroun'], ['fr', 'France'], ['us', 'États-Unis']];
                        @endphp
                        <button
                            type="button"
                            @click="showCountriesModal = true"
                            aria-label="Voir les plus de 150 pays disponibles"
                            class="group flex w-full items-center justify-between gap-3 rounded-full border border-white/15 bg-white/10 py-2 pl-2.5 pr-4 text-left transition hover:bg-white/15"
                        >
                            <span class="flex items-center -space-x-2.5" aria-hidden="true">
                                @foreach ($flags as $i => $flag)
                                    <span @class([
                                        'relative flex h-8 w-8 shrink-0 items-center justify-center overflow-hidden rounded-full bg-slate-600 text-[9px] font-bold uppercase text-white ring-2 ring-primary',
                                        'hidden min-[420px]:flex' => $i === 5 || $i === 6,
                                        'hidden min-[540px]:flex' => $i === 7,
                                    ]) style="z-index: {{ 10 - $i }}">
                                        {{ $flag[0] }}
                                        <img
                                            src="https://flagcdn.com/w80/{{ $flag[0] }}.png"
                                            alt=""
                                            width="32" height="32"
                                            class="absolute inset-0 h-full w-full object-cover"
                                            onerror="this.remove()"
                                        >
                                    </span>
                                @endforeach
                            </span>

                            <span class="min-w-0 text-right leading-tight">
                                <span class="block text-sm font-bold text-white">150+ pays</span>
                                <span class="block truncate text-[11px] text-white/60 group-hover:text-white/80">disponibles · <span class="font-semibold text-accent">tout voir</span></span>
                            </span>
                        </button>
                    </div>
                </div>

                {{-- ═══ BOTTOM WHITE ORDER FORM ═══ --}}
                <div id="acheter" class="scroll-mt-4 bg-white dark:bg-slate-900">
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

            @include('partials.home.how-it-works')
            @include('partials.home.faq-section')

            @include('partials.home.link-bar')
        </div>

        @include('partials.home.countries-modal')
        @include('partials.home.waiting-modal')
        @include('partials.home.success-modal')
        @include('partials.home.error-modal')
        @include('partials.home.account-modal')
        @include('partials.home.legal-modals')
    </body>
</html>
