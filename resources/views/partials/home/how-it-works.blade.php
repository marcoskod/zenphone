{{-- Explains the service to people arriving cold from an ad: what it is, how it works, what it's for, why trust it. --}}
@php
    $steps = [
        ['icon' => 'fa-earth-africa', 'title' => 'Choisissez un pays et une application', 'text' => 'Bénin, Sénégal, France, États-Unis… et l\'application pour laquelle vous voulez créer un compte (WhatsApp, Telegram, Google, TikTok…).'],
        ['icon' => 'fa-mobile-screen-button', 'title' => 'Payez en Mobile Money', 'text' => 'MTN, Moov, Orange ou carte bancaire, via FedaPay. Vous payez uniquement le prix du numéro, sans recharge préalable.'],
        ['icon' => 'fa-message', 'title' => 'Recevez votre code en direct', 'text' => 'Copiez le numéro dans l\'application, le code SMS s\'affiche ici en quelques secondes. Collez-le, votre compte est créé.'],
    ];

    $uses = [
        ['icon' => 'fa-user-shield', 'text' => 'Protéger votre vrai numéro de téléphone'],
        ['icon' => 'fa-briefcase', 'text' => 'Créer des comptes pour votre activité ou votre entreprise'],
        ['icon' => 'fa-globe', 'text' => 'Accéder à des services réservés à un autre pays'],
        ['icon' => 'fa-flask', 'text' => 'Tester une application sans engager votre numéro'],
    ];

    $guarantees = [
        ['icon' => 'fa-rotate-left', 'title' => 'Remboursé si aucun SMS', 'text' => 'Automatique, même si vous fermez la page.'],
        ['icon' => 'fa-shield-halved', 'title' => 'Paiement sécurisé', 'text' => 'Traité par FedaPay, jamais stocké chez nous.'],
        ['icon' => 'fa-bolt', 'title' => 'Code en quelques secondes', 'text' => 'Suivi en direct avec un compte à rebours.'],
        ['icon' => 'fa-earth-africa', 'title' => 'Plus de 150 pays', 'text' => 'Et des centaines d\'applications.'],
    ];
@endphp

<section class="mt-10 w-full max-w-lg" aria-labelledby="how-title">
    <h2 id="how-title" class="text-center text-lg font-bold text-secondary dark:text-light">Comment ça marche&nbsp;?</h2>
    <p class="mt-1 text-center text-xs text-secondary/60 dark:text-light/60">Trois étapes, moins d'une minute.</p>

    <ol class="mt-5 space-y-3">
        @foreach ($steps as $i => $step)
            <li class="flex gap-4 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-primary to-secondary text-lg text-white shadow-md">
                    <i class="fa-solid {{ $step['icon'] }}" aria-hidden="true"></i>
                </span>
                <div class="min-w-0">
                    <h3 class="text-sm font-semibold text-secondary dark:text-light">
                        <span class="text-accent">{{ $i + 1 }}.</span> {{ $step['title'] }}
                    </h3>
                    <p class="mt-1 text-xs leading-relaxed text-secondary/70 dark:text-light/70">{{ $step['text'] }}</p>
                </div>
            </li>
        @endforeach
    </ol>
</section>

<section class="mt-10 w-full max-w-lg" aria-labelledby="uses-title">
    <h2 id="uses-title" class="text-center text-lg font-bold text-secondary dark:text-light">À quoi ça sert&nbsp;?</h2>
    <p class="mt-1 text-center text-xs text-secondary/60 dark:text-light/60">Un numéro temporaire d'un autre pays, pour recevoir un code de vérification.</p>

    <ul class="mt-5 grid grid-cols-2 gap-3">
        @foreach ($uses as $use)
            <li class="flex flex-col items-center gap-2 rounded-2xl border border-slate-200 bg-white p-4 text-center shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-accent/15 text-accent">
                    <i class="fa-solid {{ $use['icon'] }}" aria-hidden="true"></i>
                </span>
                <span class="text-xs font-medium leading-snug text-secondary dark:text-light">{{ $use['text'] }}</span>
            </li>
        @endforeach
    </ul>
</section>

<section class="mt-10 w-full max-w-lg" aria-labelledby="trust-title">
    <h2 id="trust-title" class="text-center text-lg font-bold text-secondary dark:text-light">Pourquoi Zenphone&nbsp;?</h2>

    <ul class="mt-5 grid grid-cols-2 gap-3">
        @foreach ($guarantees as $g)
            <li class="rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <i class="fa-solid {{ $g['icon'] }} text-lg text-success" aria-hidden="true"></i>
                <h3 class="mt-2 text-xs font-semibold text-secondary dark:text-light">{{ $g['title'] }}</h3>
                <p class="mt-0.5 text-[11px] leading-snug text-secondary/60 dark:text-light/60">{{ $g['text'] }}</p>
            </li>
        @endforeach
    </ul>

    <a
        href="#acheter"
        class="mt-6 flex min-h-[48px] w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-primary to-secondary px-4 py-3 text-sm font-bold text-white shadow-lg transition hover:opacity-90"
    >
        <i class="fa-solid fa-bolt" aria-hidden="true"></i>
        Obtenir mon numéro maintenant
    </a>
</section>
