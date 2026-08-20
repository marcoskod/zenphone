@php
    $adminLinks = [
        ['route' => 'admin.dashboard', 'label' => 'Tableau de bord', 'icon' => 'fa-gauge'],
    ];
@endphp

<div class="flex flex-wrap gap-2 border-b border-slate-200 dark:border-slate-800">
    @foreach ($adminLinks as $link)
        <a
            href="{{ route($link['route']) }}"
            class="flex items-center gap-2 border-b-2 px-4 py-2 text-sm font-medium transition {{ request()->routeIs($link['route']) ? 'border-primary text-primary' : 'border-transparent text-secondary/60 hover:text-secondary dark:text-light/60 dark:hover:text-light' }}"
        >
            <i class="fa-solid {{ $link['icon'] }}"></i>
            {{ $link['label'] }}
        </a>
    @endforeach
</div>
