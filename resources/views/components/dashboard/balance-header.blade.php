<div class="flex flex-col items-start justify-between gap-4 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:flex-row sm:items-center">
    <div>
        <p class="text-sm font-medium text-secondary/60 dark:text-light/60">Solde disponible</p>
        <p class="mt-1 text-3xl font-bold text-secondary dark:text-light">
            {{ number_format(auth()->user()->balance, 0, ',', ' ') }} FCFA
        </p>
    </div>

    <a href="{{ route('topup') }}" class="inline-flex items-center gap-2 rounded-full bg-gradient-to-r from-primary to-accent px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:opacity-90">
        <i class="fa-solid fa-wallet"></i>
        Recharger
    </a>
</div>
