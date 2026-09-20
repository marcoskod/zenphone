<nav class="mt-6 flex w-full max-w-lg flex-wrap items-center justify-center gap-x-5 gap-y-2 px-4 text-xs text-secondary/60 dark:text-light/60">
    <button type="button" @click="openLegal('faq')" class="transition hover:text-primary">FAQ</button>
    <button type="button" @click="openLegal('contact')" class="transition hover:text-primary">Contact</button>
    <button type="button" @click="openLegal('about')" class="transition hover:text-primary">À propos</button>
    <button type="button" @click="openLegal('privacy')" class="transition hover:text-primary">Politique de confidentialité</button>
    <button type="button" @click="openLegal('cgv')" class="transition hover:text-primary">CGV</button>
    <button type="button" @click="openLegal('mentions')" class="transition hover:text-primary">Mentions légales</button>
</nav>

<p class="mt-3 text-center text-[11px] text-secondary/40 dark:text-light/40">
    &copy; {{ now()->year }} Zenphone. Tous droits réservés.
</p>
