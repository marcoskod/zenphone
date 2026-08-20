<header
    x-data="{ mobileOpen: false, dark: document.documentElement.classList.contains('dark') }"
    x-init="$watch('dark', value => { localStorage.setItem('darkMode', value); document.documentElement.classList.toggle('dark', value); })"
    class="sticky top-0 z-50 border-b border-slate-200 bg-light/90 backdrop-blur dark:border-slate-800 dark:bg-secondary/90"
>
    <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-secondary dark:text-light">
            <span class="flex h-9 w-9 items-center justify-center rounded-xl bg-primary text-white">
                <i class="fa-solid fa-comment-sms"></i>
            </span>
            Zen<span class="text-accent">Sms</span>
        </a>

        <nav class="hidden items-center gap-8 md:flex">
            <a href="{{ route('home') }}" class="text-sm font-medium text-secondary/80 transition hover:text-primary dark:text-light/80 dark:hover:text-primary">Accueil</a>
            <a href="{{ route('pricing') }}" class="text-sm font-medium text-secondary/80 transition hover:text-primary dark:text-light/80 dark:hover:text-primary">Tarifs</a>
            <a href="{{ route('faq') }}" class="text-sm font-medium text-secondary/80 transition hover:text-primary dark:text-light/80 dark:hover:text-primary">FAQ</a>
        </nav>

        <div class="hidden items-center gap-4 md:flex">
            <button
                @click="dark = !dark"
                type="button"
                aria-label="Basculer le mode sombre"
                class="flex h-9 w-9 items-center justify-center rounded-full text-secondary/70 transition hover:bg-slate-200/60 hover:text-primary dark:text-light/70 dark:hover:bg-slate-800"
            >
                <i class="fa-solid fa-sun" x-show="dark" x-cloak></i>
                <i class="fa-solid fa-moon" x-show="!dark"></i>
            </button>
            <a href="{{ route('login') }}" class="text-sm font-medium text-secondary/80 transition hover:text-primary dark:text-light/80 dark:hover:text-primary">Connexion</a>
            <a href="{{ route('register') }}" class="rounded-full bg-primary px-5 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-primary/90">Inscription</a>
        </div>

        <button
            @click="mobileOpen = !mobileOpen"
            type="button"
            aria-label="Ouvrir le menu"
            class="flex h-9 w-9 items-center justify-center rounded-lg text-secondary hover:bg-slate-200/60 dark:text-light dark:hover:bg-slate-800 md:hidden"
        >
            <i class="fa-solid fa-bars" x-show="!mobileOpen"></i>
            <i class="fa-solid fa-xmark" x-show="mobileOpen" x-cloak></i>
        </button>
    </div>

    <div
        x-show="mobileOpen"
        x-cloak
        x-transition
        class="space-y-3 border-t border-slate-200 px-4 py-4 dark:border-slate-800 md:hidden"
    >
        <a href="{{ route('home') }}" class="block text-sm font-medium text-secondary/80 dark:text-light/80">Accueil</a>
        <a href="{{ route('pricing') }}" class="block text-sm font-medium text-secondary/80 dark:text-light/80">Tarifs</a>
        <a href="{{ route('faq') }}" class="block text-sm font-medium text-secondary/80 dark:text-light/80">FAQ</a>

        <div class="flex items-center justify-between border-t border-slate-200 pt-3 dark:border-slate-800">
            <button
                @click="dark = !dark"
                type="button"
                aria-label="Basculer le mode sombre"
                class="flex items-center gap-2 text-sm font-medium text-secondary/80 dark:text-light/80"
            >
                <i class="fa-solid fa-sun" x-show="dark" x-cloak></i>
                <i class="fa-solid fa-moon" x-show="!dark"></i>
                <span>Mode sombre</span>
            </button>
        </div>

        <div class="flex flex-col gap-3 pt-2">
            <a href="{{ route('login') }}" class="text-center text-sm font-medium text-secondary/80 dark:text-light/80">Connexion</a>
            <a href="{{ route('register') }}" class="rounded-full bg-primary px-5 py-2 text-center text-sm font-semibold text-white">Inscription</a>
        </div>
    </div>
</header>
