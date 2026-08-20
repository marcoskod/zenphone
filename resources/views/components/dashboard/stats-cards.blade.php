@props(['stats'])

@php
    $cards = [
        [
            'icon' => 'fa-phone',
            'label' => 'Numéros achetés',
            'value' => number_format($stats['numbers_bought'], 0, ',', ' '),
        ],
        [
            'icon' => 'fa-comment-sms',
            'label' => 'SMS reçus',
            'value' => number_format($stats['sms_received'], 0, ',', ' '),
        ],
        [
            'icon' => 'fa-wallet',
            'label' => 'Solde actuel',
            'value' => number_format($stats['balance'], 0, ',', ' ') . ' FCFA',
        ],
        [
            'icon' => 'fa-sack-dollar',
            'label' => 'Dépense totale',
            'value' => number_format($stats['total_spent'], 0, ',', ' ') . ' FCFA',
        ],
    ];
@endphp

<div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
    @foreach ($cards as $card)
        <div class="flex items-center gap-4 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900">
            <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary">
                <i class="fa-solid {{ $card['icon'] }} text-xl"></i>
            </div>
            <div>
                <p class="text-xl font-bold text-secondary dark:text-light">{{ $card['value'] }}</p>
                <p class="text-xs text-secondary/60 dark:text-light/60">{{ $card['label'] }}</p>
            </div>
        </div>
    @endforeach
</div>
