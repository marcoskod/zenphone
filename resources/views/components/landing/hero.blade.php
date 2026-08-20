<section class="relative overflow-hidden bg-light dark:bg-secondary">
    <div class="mx-auto max-w-7xl px-4 py-20 sm:px-6 sm:py-28 lg:px-8">
        <div class="mx-auto max-w-3xl text-center">
            <span class="inline-flex items-center gap-2 rounded-full bg-primary/10 px-4 py-1.5 text-sm font-medium text-primary">
                <i class="fa-solid fa-shield-halved"></i>
                Numéros vérifiés, réception instantanée
            </span>

            <h1 class="mt-6 text-4xl font-bold tracking-tight text-secondary dark:text-light sm:text-5xl lg:text-6xl">
                Recevez vos codes SMS
                <span class="text-primary">sans carte SIM</span>
            </h1>

            <p class="mt-6 text-lg text-secondary/70 dark:text-light/70">
                Achetez un numéro virtuel en quelques secondes pour vérifier WhatsApp, Google, Instagram,
                TikTok, Telegram et bien d'autres services — payez facilement en Orange Money, Wave, MTN MoMo
                ou Moov Money.
            </p>

            <div class="mt-10 flex flex-col items-center justify-center gap-4 sm:flex-row">
                <a href="{{ route('register') }}" class="btn-zen-cta">
                    <span>Créer mon compte gratuitement</span>
                    <i class="fa-solid fa-arrow-right"></i>
                </a>
                <a href="{{ route('pricing') }}" class="text-sm font-semibold text-secondary transition hover:text-primary dark:text-light dark:hover:text-primary">
                    Voir les tarifs
                    <i class="fa-solid fa-chevron-right ml-1 text-xs"></i>
                </a>
            </div>

            <p class="mt-6 text-xs text-secondary/50 dark:text-light/50">
                Aucune carte bancaire requise · Recharge dès 500 FCFA
            </p>
        </div>
    </div>

    {{--
        Bespoke animated gradient CTA button in a uiverse.io-inspired style (gradient shift,
        lift-on-hover, glow shadow). Built from scratch rather than copy-pasting a specific
        uiverse.io submission, to avoid unclear per-component licensing and unvetted CSS/JS
        conflicting with the Tailwind/Alpine setup.
    --}}
    <style>
        .btn-zen-cta {
            position: relative;
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.85rem 2rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: #fff;
            border-radius: 9999px;
            background: linear-gradient(115deg, #0EA5A4 0%, #0EA5A4 45%, #F59E0B 100%);
            background-size: 200% 200%;
            background-position: 0% 50%;
            box-shadow: 0 8px 24px -8px rgba(14, 165, 164, 0.55);
            transition: background-position 0.5s ease, transform 0.2s ease, box-shadow 0.2s ease;
        }
        .btn-zen-cta:hover {
            background-position: 100% 50%;
            transform: translateY(-2px);
            box-shadow: 0 12px 28px -6px rgba(245, 158, 11, 0.45);
        }
        .btn-zen-cta:active {
            transform: translateY(0);
        }
        .btn-zen-cta i {
            transition: transform 0.2s ease;
        }
        .btn-zen-cta:hover i {
            transform: translateX(3px);
        }
    </style>
</section>
