@php
    $categories = [
        'Compte' => [
            [
                'q' => "Ai-je besoin de créer un compte séparément avant d'acheter ?",
                'a' => "Non. Renseignez simplement votre email et un mot de passe au moment de l'achat : si l'email est nouveau, votre compte est créé automatiquement ; s'il existe déjà, votre mot de passe habituel vous connecte.",
            ],
            [
                'q' => 'Puis-je changer le mot de passe de mon compte ?',
                'a' => 'Oui, depuis votre espace « Mon compte » ou via le formulaire de contact si vous avez besoin d\'aide.',
            ],
        ],
        'Paiement' => [
            [
                'q' => 'Comment payer ?',
                'a' => 'Directement pour chaque numéro, sans recharge préalable : en Mobile Money (Orange Money, Wave, MTN MoMo, Moov Money) ou par carte bancaire, via FedaPay.',
            ],
            [
                'q' => 'Le paiement est-il sécurisé ?',
                'a' => "Oui. Tous les paiements sont traités par FedaPay et vérifiés côté serveur avant la livraison de votre numéro — nous ne stockons jamais vos identifiants bancaires ou Mobile Money.",
            ],
        ],
        'Numéros / SMS' => [
            [
                'q' => 'Combien de temps le numéro reste-t-il actif ?',
                'a' => 'Chaque numéro reste disponible entre 5 et 20 minutes selon le service choisi, le temps de recevoir votre code de vérification.',
            ],
            [
                'q' => 'Puis-je réutiliser le même numéro plusieurs fois ?',
                'a' => "Non, chaque numéro est à usage unique pour un service donné. Pour un nouveau code, effectuez un nouvel achat.",
            ],
        ],
        'Remboursement' => [
            [
                'q' => "Que se passe-t-il si je ne reçois pas de SMS ?",
                'a' => "Si aucun SMS n'arrive avant l'expiration du numéro, la commande est automatiquement annulée et son montant recrédité sur votre compte (utilisé à votre prochain achat), même si vous avez fermé la page. Consultez nos CGV pour le détail.",
            ],
            [
                'q' => 'Puis-je être remboursé après avoir reçu le code ?',
                'a' => "Non. Une fois le SMS livré, la commande est considérée comme honorée et ne peut plus être annulée ni remboursée.",
            ],
        ],
    ];
@endphp

<div x-data="{ open: null }" class="space-y-6">
    @foreach ($categories as $category => $items)
        @php($categoryIndex = $loop->index)
        <div>
            <h4 class="mb-2 text-xs font-bold uppercase tracking-wide text-primary">{{ $category }}</h4>
            <div class="space-y-2">
                @foreach ($items as $index => $item)
                    @php($key = $categoryIndex.'-'.$index)
                    <div class="rounded-lg border border-slate-200 dark:border-slate-800">
                        <button
                            type="button"
                            @click="open = open === '{{ $key }}' ? null : '{{ $key }}'"
                            class="flex w-full items-center justify-between gap-3 px-4 py-3 text-left"
                        >
                            <span class="text-sm font-medium text-secondary dark:text-light">{{ $item['q'] }}</span>
                            <i class="fa-solid fa-chevron-down shrink-0 text-xs text-secondary/40 transition-transform" :class="{ 'rotate-180': open === '{{ $key }}' }"></i>
                        </button>
                        <div x-show="open === '{{ $key }}'" x-cloak x-transition class="px-4 pb-3 text-sm text-secondary/70 dark:text-light/70">
                            {{ $item['a'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endforeach
</div>
