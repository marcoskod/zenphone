<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Zenphone') }}</title>

        {{-- Set the dark class before first paint to avoid a flash of the wrong theme. --}}
        <script>
            if (localStorage.getItem('darkMode') === 'true' || (!('darkMode' in localStorage) && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.classList.add('dark');
            }
        </script>

        <!-- Font Awesome (icons) -->
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-light font-sans text-secondary antialiased dark:bg-secondary dark:text-light">
        <div class="flex min-h-screen flex-col items-center justify-center px-4 py-10">
            <a href="{{ route('home') }}" class="flex items-center gap-2 text-xl font-bold text-secondary dark:text-light">
                <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary text-white">
                    <i class="fa-solid fa-comment-sms"></i>
                </span>
                Zen<span class="text-accent">Sms</span>
            </a>

            <div class="mt-8 w-full sm:max-w-md">
                <div class="rounded-2xl border border-slate-200 bg-white px-6 py-8 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:px-8">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
