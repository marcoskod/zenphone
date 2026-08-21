@php
    $unreadNotifications = auth()->user()->unreadNotifications;
@endphp

<div x-data="{ open: false, count: {{ $unreadNotifications->count() }} }" class="relative">
    <button
        @click="open = !open"
        type="button"
        aria-label="Notifications"
        class="relative flex h-9 w-9 items-center justify-center rounded-full text-secondary/70 transition hover:bg-slate-200/60 hover:text-primary dark:text-light/70 dark:hover:bg-slate-800"
    >
        <i class="fa-solid fa-bell"></i>
        <span
            x-show="count > 0"
            x-cloak
            x-text="count"
            class="absolute -right-0.5 -top-0.5 flex h-4 min-w-[1rem] items-center justify-center rounded-full bg-accent px-1 text-[10px] font-bold text-white"
        ></span>
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
            <button
                type="button"
                x-data="{ read: false }"
                x-show="! read"
                @click="
                    read = true;
                    count--;
                    fetch('/api/notifications/{{ $notification->id }}/read', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content, Accept: 'application/json' },
                    });
                "
                class="block w-full rounded-xl px-3 py-2 text-left text-sm text-secondary transition hover:bg-slate-100 dark:text-light dark:hover:bg-slate-800"
            >
                {{ $notification->data['message'] ?? 'Nouvelle notification' }}
            </button>
        @empty
            <p class="px-3 py-6 text-center text-sm text-secondary/60 dark:text-light/60">Aucune notification pour le moment.</p>
        @endforelse
    </div>
</div>
