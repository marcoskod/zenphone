<x-guest-layout>
    <div class="mb-6 text-center">
        <h1 class="text-2xl font-bold text-secondary dark:text-light">Créer un compte</h1>
        <p class="mt-1 text-sm text-secondary/60 dark:text-light/60">Recevez vos codes SMS en quelques minutes.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-5">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" :value="__('Nom complet')" />
            <x-text-input id="name" class="mt-1 block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" placeholder="Aïssatou Diallo" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div>
            <x-input-label for="email" :value="__('Adresse email')" />
            <x-text-input id="email" class="mt-1 block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" placeholder="vous@exemple.com" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div>
            <x-input-label for="password" :value="__('Mot de passe')" />

            <x-text-input id="password" class="mt-1 block w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div>
            <x-input-label for="password_confirmation" :value="__('Confirmer le mot de passe')" />

            <x-text-input id="password_confirmation" class="mt-1 block w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- CGU -->
        <div>
            <label for="cgu" class="flex items-start gap-2">
                <input id="cgu" type="checkbox" name="cgu" value="1" required
                    {{ old('cgu') ? 'checked' : '' }}
                    class="mt-0.5 rounded border-slate-300 text-primary focus:ring-primary dark:border-slate-700 dark:bg-slate-800">
                <span class="text-sm text-secondary/70 dark:text-light/70">
                    J'accepte les
                    <a href="{{ route('cgv') }}" target="_blank" class="font-medium text-primary underline hover:text-primary/80">conditions générales de vente</a>.
                </span>
            </label>
            <x-input-error :messages="$errors->get('cgu')" class="mt-2" />
        </div>

        <x-primary-button class="w-full">
            {{ __('Créer mon compte') }}
        </x-primary-button>

        <p class="text-center text-sm text-secondary/60 dark:text-light/60">
            Déjà inscrit ?
            <a href="{{ route('login') }}" class="font-medium text-primary hover:text-primary/80">Se connecter</a>
        </p>
    </form>
</x-guest-layout>
