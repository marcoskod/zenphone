@extends('layouts.marketing')

@section('title', ($title ?? 'Bientôt disponible') . ' — Zen_Sms')

@section('content')
    <section class="mx-auto flex max-w-3xl flex-col items-center px-4 py-24 text-center sm:px-6 lg:px-8">
        <span class="flex h-16 w-16 items-center justify-center rounded-2xl bg-primary/10 text-primary">
            <i class="fa-solid {{ $icon ?? 'fa-hourglass-half' }} text-2xl"></i>
        </span>
        <h1 class="mt-6 text-3xl font-bold text-secondary dark:text-light sm:text-4xl">{{ $title ?? 'Bientôt disponible' }}</h1>
        <p class="mt-4 text-secondary/70 dark:text-light/70">
            {{ $message ?? 'Cette page est en cours de préparation et sera disponible très prochainement.' }}
        </p>
        <a href="{{ route('home') }}" class="mt-8 inline-flex items-center gap-2 rounded-full bg-primary px-6 py-3 text-sm font-semibold text-white transition hover:bg-primary/90">
            <i class="fa-solid fa-arrow-left"></i>
            Retour à l'accueil
        </a>
    </section>
@endsection
