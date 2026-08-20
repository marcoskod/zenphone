@php
    $unreadNotifications = auth()->user()->unreadNotifications;
@endphp

<div x-data="{ open: false }" class="relative">
    <button
        @click="open = !open"
        type="button"
        aria-label="Notifications"
        class="relative flex h-9 w-9 items-center justify-center rounded-full text-secondary/70 transition hover:bg-slate-200/60 hover:text-primary dark:text-light/70 dark:hover:bg-slate-800"
    >
        <i class="fa-solid fa-bell"></i>
        @if ($unreadNotifications->isNotEmpty())
            <span class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-accent px-1 text-[10px] font-bold text-white">
                {{ $unreadNotifications->count() }}
            </span>
        @endif
    </button>

    <div
        x-show="open"
        x-cloak
        x-transition
        @click.outside="open = false"
        class="absolute right-0 z-50 mt-2 w-80 rounded-2xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-800 dark:bg-slate-900"
    >
        <p class="px-3 py-2 text-xs font-semibold uppercase text-secondary/50 dark:text-light/50">Notifications</p>

        @forelse ($unreadNotifications as $notification)
            <div class="rounded-xl px-3 py-2 text-sm text-secondary dark:text-light">
                {{ $notification->data['message'] ?? 'Nouvelle notification' }}
            </div>
        @empty
            <p class="px-3 py-6 text-center text-sm text-secondary/60 dark:text-light/60">Aucune notification pour le moment.</p>
        @endforelse
    </div>
</div>
