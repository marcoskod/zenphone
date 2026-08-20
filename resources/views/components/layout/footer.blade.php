<footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-secondary">
    <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid grid-cols-2 gap-8 md:grid-cols-4">
            <div class="col-span-2 md:col-span-1">
                <a href="{{ route('home') }}" class="flex items-center gap-2 text-lg font-bold text-secondary dark:text-light">
                    <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-primary text-white">
                        <i class="fa-solid fa-comment-sms text-sm"></i>
                    </span>
                    Zen<span class="text-accent">Sms</span>
                </a>
                <p class="mt-4 text-sm text-secondary/70 dark:text-light/70">
                    Numéros virtuels pour recevoir vos codes SMS de vérification, payés en Mobile Money
                    partout en Afrique de l'Ouest.
                </p>
                <div class="mt-4 flex gap-3 text-secondary/60 dark:text-light/60">
                    <a href="#" aria-label="Facebook" class="transition hover:text-primary"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" aria-label="X (Twitter)" class="transition hover:text-primary"><i class="fa-brands fa-x-twitter"></i></a>
                    <a href="#" aria-label="Instagram" class="transition hover:text-primary"><i class="fa-brands fa-instagram"></i></a>
                    <a href="#" aria-label="WhatsApp" class="transition hover:text-primary"><i class="fa-brands fa-whatsapp"></i></a>
                </div>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-secondary dark:text-light">Services populaires</h3>
                <ul class="mt-4 space-y-2 text-sm text-secondary/70 dark:text-light/70">
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">WhatsApp</a></li>
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">Google</a></li>
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">Instagram</a></li>
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">Telegram</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-secondary dark:text-light">Pays populaires</h3>
                <ul class="mt-4 space-y-2 text-sm text-secondary/70 dark:text-light/70">
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">Côte d'Ivoire</a></li>
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">Sénégal</a></li>
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">Mali</a></li>
                    <li><a href="{{ route('pricing') }}" class="transition hover:text-primary">Bénin</a></li>
                </ul>
            </div>

            <div>
                <h3 class="text-sm font-semibold uppercase tracking-wide text-secondary dark:text-light">Informations légales</h3>
                <ul class="mt-4 space-y-2 text-sm text-secondary/70 dark:text-light/70">
                    <li><a href="{{ route('cgv') }}" class="transition hover:text-primary">CGV</a></li>
                    <li><a href="{{ route('mentions-legales') }}" class="transition hover:text-primary">Mentions légales</a></li>
                    <li><a href="{{ route('confidentialite') }}" class="transition hover:text-primary">Confidentialité</a></li>
                    <li><a href="{{ route('contact') }}" class="transition hover:text-primary">Contact</a></li>
                </ul>
            </div>
        </div>

        <div class="mt-10 border-t border-slate-200 pt-6 text-center text-sm text-secondary/60 dark:border-slate-800 dark:text-light/60">
            &copy; {{ now()->year }} Zen_Sms. Tous droits réservés.
        </div>
    </div>
</footer>
