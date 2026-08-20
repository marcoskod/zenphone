@php
    $steps = [
        ['icon' => 'fa-user-plus', 'title' => 'Créez un compte', 'text' => "Inscrivez-vous en moins d'une minute avec votre adresse email."],
        ['icon' => 'fa-wallet', 'title' => 'Rechargez votre solde', 'text' => 'Ajoutez des FCFA via Orange Money, Wave, MTN MoMo ou Moov Money.'],
        ['icon' => 'fa-phone', 'title' => 'Choisissez un numéro', 'text' => "Sélectionnez le service et le pays, le prix s'affiche en FCFA."],
        ['icon' => 'fa-comment-sms', 'title' => 'Recevez le SMS', 'text' => 'Le code de vérification apparaît en direct sur votre écran.'],
    ];
@endphp

<section id="comment-ca-marche" class="bg-white py-20 dark:bg-slate-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <h2 class="text-3xl font-bold text-secondary dark:text-light sm:text-4xl">Comment ça marche</h2>
            <p class="mt-4 text-secondary/70 dark:text-light/70">Quatre étapes simples pour recevoir votre code de vérification.</p>
        </div>

        <div class="mt-16 grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($steps as $index => $step)
                <div class="relative rounded-2xl border border-slate-200 bg-light p-6 dark:border-slate-800 dark:bg-secondary">
                    <span class="absolute -top-4 -left-2 flex h-8 w-8 items-center justify-center rounded-full bg-accent text-sm font-bold text-white">
                        {{ $index + 1 }}
                    </span>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-primary/10 text-primary">
                        <i class="fa-solid {{ $step['icon'] }} text-xl"></i>
                    </div>
                    <h3 class="mt-4 font-semibold text-secondary dark:text-light">{{ $step['title'] }}</h3>
                    <p class="mt-2 text-sm text-secondary/70 dark:text-light/70">{{ $step['text'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
