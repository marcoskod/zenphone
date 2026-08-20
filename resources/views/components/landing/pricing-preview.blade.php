{{--
    Prices below are illustrative examples for the landing page preview. Live FCFA pricing,
    computed from the 5sim rate plus configured margin, is implemented by PricingService in
    a later phase (action_05 / action_09).
--}}
@php
    $items = [
        ['service' => 'WhatsApp', 'icon' => 'fa-whatsapp', 'price' => '350'],
        ['service' => 'Google', 'icon' => 'fa-google', 'price' => '400'],
        ['service' => 'Instagram', 'icon' => 'fa-instagram', 'price' => '300'],
        ['service' => 'Telegram', 'icon' => 'fa-telegram', 'price' => '250'],
    ];
@endphp

<section class="bg-white py-20 dark:bg-slate-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-secondary dark:text-light sm:text-4xl">Des tarifs simples et transparents</h2>
            <p class="mt-4 text-secondary/70 dark:text-light/70">Quelques exemples de prix, en FCFA. Le tarif exact dépend du service et du pays choisis.</p>
        </div>

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($items as $item)
                <div class="rounded-2xl border border-slate-200 p-6 text-center transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-800">
                    <i class="fa-brands {{ $item['icon'] }} text-3xl text-primary"></i>
                    <h3 class="mt-4 font-semibold text-secondary dark:text-light">{{ $item['service'] }}</h3>
                    <p class="mt-2 text-2xl font-bold text-secondary dark:text-light">
                        {{ $item['price'] }} <span class="text-sm font-normal text-secondary/60 dark:text-light/60">FCFA</span>
                    </p>
                    <p class="mt-1 text-xs text-secondary/50 dark:text-light/50">à partir de</p>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('pricing') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:text-primary/80">
                Voir tous les tarifs
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
