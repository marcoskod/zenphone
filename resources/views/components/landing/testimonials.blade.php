@php
    $testimonials = [
        [
            'name' => 'Fatou D.',
            'location' => 'Dakar, Sénégal',
            'text' => "J'ai reçu mon code WhatsApp en moins de 2 minutes, payé avec Orange Money. Super simple !",
            'rating' => 5,
        ],
        [
            'name' => 'Ibrahima K.',
            'location' => "Abidjan, Côte d'Ivoire",
            'text' => 'Parfait pour créer plusieurs comptes professionnels sans donner mon vrai numéro. Je recommande.',
            'rating' => 5,
        ],
        [
            'name' => 'Aminata S.',
            'location' => 'Bamako, Mali',
            'text' => "Le paiement en Wave était instantané et le support a répondu rapidement à ma question.",
            'rating' => 4,
        ],
        [
            'name' => 'Junior A.',
            'location' => 'Cotonou, Bénin',
            'text' => 'Solution fiable pour vérifier mes comptes TikTok et Instagram. Prix corrects en FCFA.',
            'rating' => 5,
        ],
    ];
@endphp

<section class="bg-light py-20 dark:bg-secondary">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-secondary dark:text-light sm:text-4xl">Ils nous font confiance</h2>
            <p class="mt-4 text-secondary/70 dark:text-light/70">Des milliers d'utilisateurs en Afrique de l'Ouest utilisent Zenphone au quotidien.</p>
        </div>

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($testimonials as $testimonial)
                <figure class="flex flex-col rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
                    <div class="flex gap-1 text-accent">
                        @for ($i = 0; $i < 5; $i++)
                            <i class="fa-solid fa-star text-xs {{ $i >= $testimonial['rating'] ? 'opacity-20' : '' }}"></i>
                        @endfor
                    </div>
                    <blockquote class="mt-4 flex-1 text-sm text-secondary/80 dark:text-light/80">
                        « {{ $testimonial['text'] }} »
                    </blockquote>
                    <figcaption class="mt-4 text-sm font-semibold text-secondary dark:text-light">
                        {{ $testimonial['name'] }}
                        <span class="block text-xs font-normal text-secondary/50 dark:text-light/50">{{ $testimonial['location'] }}</span>
                    </figcaption>
                </figure>
            @endforeach
        </div>
    </div>
</section>
