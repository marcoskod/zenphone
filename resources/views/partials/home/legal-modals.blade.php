{{-- Shared legal-modal shell, one instance per topic, toggled via the legalModal state --}}

@php
    $legalPages = [
        'faq' => 'FAQ',
        'contact' => 'Contact',
        'about' => 'À propos',
        'privacy' => 'Confidentialité',
        'cgv' => 'CGV',
        'mentions' => 'Mentions légales',
    ];
@endphp

@foreach ($legalPages as $id => $label)
    <div
        x-show="legalModal === '{{ $id }}'"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-secondary/60 p-4"
    >
        <div class="flex max-h-[88vh] w-full max-w-lg flex-col overflow-hidden rounded-2xl bg-white shadow-2xl dark:bg-slate-900">
            <div class="relative shrink-0 bg-gradient-to-br from-secondary to-primary p-5">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-accent">Zenphone &rsaquo; {{ $label }}</p>
                <h3 class="mt-1 pr-8 text-lg font-bold text-white">{{ $label }}</h3>
                <p class="mt-1 text-xs text-white/50">Dernière mise à jour : {{ now()->translatedFormat('F Y') }}</p>
                <button
                    @click="legalModal = null"
                    type="button"
                    aria-label="Fermer"
                    class="absolute right-4 top-4 flex h-7 w-7 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20"
                >
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>

            <div class="flex-1 overflow-y-auto p-5">
                @if ($id === 'faq')
                    @include('partials.home.legal.faq')
                @elseif ($id === 'contact')
                    @include('partials.home.legal.contact')
                @elseif ($id === 'about')
                    @include('partials.home.legal.about')
                @elseif ($id === 'privacy')
                    @include('partials.home.legal.privacy')
                @elseif ($id === 'cgv')
                    @include('partials.home.legal.cgv')
                @elseif ($id === 'mentions')
                    @include('partials.home.legal.mentions')
                @endif
            </div>

            <div class="shrink-0 border-t border-slate-100 p-4 dark:border-slate-800">
                <button
                    @click="legalModal = null"
                    type="button"
                    class="w-full rounded-lg bg-gradient-to-r from-primary to-secondary px-4 py-2.5 text-sm font-semibold text-white"
                >
                    Fermer
                </button>
            </div>
        </div>
    </div>
@endforeach
