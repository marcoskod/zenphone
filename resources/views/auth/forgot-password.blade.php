<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-secondary dark:text-light">Mot de passe oublié</h1>
    </div>

    <div class="mb-4 text-sm text-secondary/70 dark:text-light/70">
        {{ __("Pas de problème. Indiquez-nous votre adresse email et nous vous enverrons un lien pour réinitialiser votre mot de passe.") }}
    </div>

    <!-- Session Status -->
    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-5">
        @csrf

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Adresse email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autofocus placeholder="vous@exemple.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Envoyer le lien de réinitialisation') }}
        </x-primary-button>

        <p class="text-center text-sm text-secondary/60 dark:text-light/60">
            <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary/80">Retour à la connexion</a>
        </p>
    </form>
</x-guest-layout>
