@props(['active'])

<div class="flex gap-2 border-b border-slate-200 dark:border-slate-800">
    <a
        href="{{ route('history') }}"
        class="border-b-2 px-4 py-2 text-sm font-medium transition {{ $active === 'orders' ? 'border-primary text-primary' : 'border-transparent text-secondary/60 hover:text-secondary dark:text-light/60 dark:hover:text-light' }}"
    >
        <i class="fa-solid fa-phone"></i>
        Mes numéros
    </a>
    <a
        href="{{ route('history.topups') }}"
        class="border-b-2 px-4 py-2 text-sm font-medium transition {{ $active === 'topups' ? 'border-primary text-primary' : 'border-transparent text-secondary/60 hover:text-secondary dark:text-light/60 dark:hover:text-light' }}"
    >
        <i class="fa-solid fa-wallet"></i>
        Mes recharges
    </a>
</div>
