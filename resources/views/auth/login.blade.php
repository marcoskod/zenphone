<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-secondary dark:text-light">Connexion</h1>
        <p class="mt-1 text-sm text-secondary/60 dark:text-light/60">Contente de vous revoir.</p>
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Adresse email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" placeholder="vous@exemple.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Mot de passe')" />

            <x-text-input id="password" class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Remember Me -->
        <div class="flex items-center justify-between">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-slate-300 text-primary shadow-sm focus:ring-primary dark:border-slate-700 dark:bg-slate-800" name="remember">
                <span class="ms-2 text-sm text-secondary/70 dark:text-light/70">{{ __('Se souvenir de moi') }}</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-primary hover:text-primary/80" href="{{ route('password.request') }}">
                    {{ __('Mot de passe oublié ?') }}
                </a>
            @endif
        </div>

        <x-primary-button class="w-full">
            {{ __('Se connecter') }}
        </x-primary-button>

        <div class="flex items-center gap-3 text-xs uppercase text-secondary/40 dark:text-light/40">
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
            ou
            <span class="h-px flex-1 bg-slate-200 dark:bg-slate-700"></span>
        </div>

        <a href="{{ route('auth.google') }}" class="flex w-full items-center justify-center gap-2 rounded-full border border-slate-300 bg-white px-4 py-2.5 text-sm font-semibold text-secondary shadow-sm transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-800 dark:text-light dark:hover:bg-slate-700">
            <i class="fa-brands fa-google text-primary"></i>
            Continuer avec Google
        </a>

        <p class="text-center text-sm text-secondary/60 dark:text-light/60">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="font-medium text-primary hover:text-primary/80">Créer un compte</a>
        </p>
    </form>
</x-guest-layout>
