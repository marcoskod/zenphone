@php
    $faqs = [
        [
            'question' => 'Combien de temps le numéro reste-t-il actif ?',
            'answer' => "Chaque numéro reste disponible entre 5 et 20 minutes selon le service choisi, le temps de recevoir votre code de vérification.",
        ],
        [
            'question' => 'Comment recharger mon solde ?',
            'answer' => 'Vous pouvez recharger votre compte en FCFA via Orange Money, Wave, MTN MoMo ou Moov Money, à partir de 500 FCFA.',
        ],
        [
            'question' => 'Que se passe-t-il si je ne reçois pas de SMS ?',
            'answer' => "Si aucun SMS n'arrive avant l'expiration du numéro, vous pouvez annuler la commande et être remboursé selon notre politique.",
        ],
        [
            'question' => 'Mes données sont-elles en sécurité ?',
            'answer' => 'Nous ne demandons jamais votre numéro personnel. Toutes les transactions sont chiffrées et vos informations restent confidentielles.',
        ],
    ];
@endphp

<section class="bg-white py-20 dark:bg-slate-900">
    <div class="mx-auto max-w-3xl px-4 sm:px-6 lg:px-8">
        <div class="text-center">
            <h2 class="text-3xl font-bold text-secondary dark:text-light sm:text-4xl">Questions fréquentes</h2>
            <p class="mt-4 text-secondary/70 dark:text-light/70">Vous ne trouvez pas votre réponse ? Consultez la FAQ complète.</p>
        </div>

        <div class="mt-12 space-y-4" x-data="{ openIndex: null }">
            @foreach ($faqs as $index => $faq)
                <div class="rounded-2xl border border-slate-200 dark:border-slate-800">
                    <button
                        type="button"
                        @click="openIndex = openIndex === {{ $index }} ? null : {{ $index }}"
                        class="flex w-full items-center justify-between gap-4 px-6 py-4 text-left"
                    >
                        <span class="font-medium text-secondary dark:text-light">{{ $faq['question'] }}</span>
                        <i class="fa-solid fa-chevron-down text-secondary/50 transition-transform dark:text-light/50" :class="{ 'rotate-180': openIndex === {{ $index }} }"></i>
                    </button>
                    <div x-show="openIndex === {{ $index }}" x-cloak x-transition class="px-6 pb-4 text-sm text-secondary/70 dark:text-light/70">
                        {{ $faq['answer'] }}
                    </div>
                </div>
            @endforeach
        </div>

        <div class="mt-10 text-center">
            <a href="{{ route('faq') }}" class="inline-flex items-center gap-2 text-sm font-semibold text-primary transition hover:text-primary/80">
                Voir toute la FAQ
                <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
    </div>
</section>
