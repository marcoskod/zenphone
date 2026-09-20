{{-- On-page FAQ (native <details>: no JS, works everywhere, and the answers stay crawlable). --}}
@php
    $faq = [
        ['q' => 'C\'est quoi un numéro virtuel ?', 'a' => 'C\'est un vrai numéro de téléphone, d\'un autre pays, que vous utilisez le temps de recevoir un SMS de vérification. Il n\'y a aucune carte SIM à acheter ni à recevoir : tout se passe sur cette page.'],
        ['q' => 'Combien de temps pour recevoir le code ?', 'a' => 'En général quelques secondes. Le numéro reste actif entre 10 et 20 minutes : un compte à rebours s\'affiche et le code apparaît dès son arrivée.'],
        ['q' => 'Et si je ne reçois aucun SMS ?', 'a' => 'Vous êtes remboursé automatiquement. Le montant est recrédité sur votre compte et utilisé à votre achat suivant, même si vous avez fermé la page. Après la réception du code, la commande est considérée comme livrée.'],
        ['q' => 'Comment payer ?', 'a' => 'Directement pour chaque numéro, en Mobile Money (MTN, Moov, Orange…) ou par carte bancaire, via FedaPay. Pas de recharge préalable, pas d\'abonnement.'],
        ['q' => 'Dois-je créer un compte avant d\'acheter ?', 'a' => 'Non. Choisissez votre numéro, puis saisissez seulement un e-mail et un mot de passe au moment de l\'achat : votre compte est créé automatiquement. Vous retrouvez ensuite vos commandes et vos codes dans « Mon compte ».'],
        ['q' => 'Puis-je réutiliser le même numéro ?', 'a' => 'Non, chaque numéro est à usage unique pour l\'application choisie. Pour un nouveau code, faites un nouvel achat.'],
    ];

    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => array_map(fn ($item) => [
            '@type' => 'Question',
            'name' => $item['q'],
            'acceptedAnswer' => ['@type' => 'Answer', 'text' => $item['a']],
        ], $faq),
    ];
@endphp

<section class="mt-10 w-full max-w-lg" aria-labelledby="faq-title">
    <h2 id="faq-title" class="text-center text-lg font-bold text-secondary dark:text-light">Questions fréquentes</h2>

    <div class="mt-5 space-y-2.5">
        @foreach ($faq as $item)
            <details class="group rounded-2xl border border-slate-200 bg-white shadow-sm open:border-primary/40 dark:border-slate-800 dark:bg-slate-900">
                <summary class="flex min-h-[52px] cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 text-sm font-semibold text-secondary marker:hidden dark:text-light [&::-webkit-details-marker]:hidden">
                    <span>{{ $item['q'] }}</span>
                    <i class="fa-solid fa-chevron-down shrink-0 text-xs text-secondary/40 transition-transform duration-200 group-open:rotate-180 dark:text-light/40" aria-hidden="true"></i>
                </summary>
                <p class="px-4 pb-4 text-xs leading-relaxed text-secondary/70 dark:text-light/70">{{ $item['a'] }}</p>
            </details>
        @endforeach
    </div>

    <p class="mt-4 text-center text-xs text-secondary/60 dark:text-light/60">
        Une autre question ?
        <button type="button" @click="openLegal('contact')" class="font-semibold text-primary hover:underline dark:text-accent">Écrivez-nous</button>
    </p>
</section>

<script type="application/ld+json">{!! json_encode($faqSchema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG) !!}</script>
