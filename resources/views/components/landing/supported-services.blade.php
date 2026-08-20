@php
    $services = [
        ['icon' => 'fa-whatsapp', 'name' => 'WhatsApp'],
        ['icon' => 'fa-google', 'name' => 'Google'],
        ['icon' => 'fa-instagram', 'name' => 'Instagram'],
        ['icon' => 'fa-tiktok', 'name' => 'TikTok'],
        ['icon' => 'fa-telegram', 'name' => 'Telegram'],
        ['icon' => 'fa-facebook', 'name' => 'Facebook'],
    ];
@endphp

<section class="bg-light py-20 dark:bg-secondary">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-secondary dark:text-light sm:text-4xl">Services pris en charge</h2>
            <p class="mt-4 text-secondary/70 dark:text-light/70">Vérifiez votre compte sur les plateformes les plus utilisées.</p>
        </div>

        <div class="mt-16 grid grid-cols-3 gap-6 sm:grid-cols-4 md:grid-cols-6">
            @foreach ($services as $service)
                <div class="flex flex-col items-center gap-3 rounded-2xl border border-slate-200 bg-white p-6 text-center transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
                    <i class="fa-brands {{ $service['icon'] }} text-3xl text-primary"></i>
                    <span class="text-sm font-medium text-secondary dark:text-light">{{ $service['name'] }}</span>
                </div>
            @endforeach
        </div>

        <p class="mt-8 text-center text-sm text-secondary/60 dark:text-light/60">
            Et plus de 1000 autres services disponibles via notre fournisseur.
        </p>
    </div>
</section>
