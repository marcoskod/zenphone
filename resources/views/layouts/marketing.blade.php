<!DOCTYPE html>
<html lang="fr" class="scroll-smooth">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('title', config('app.name', 'Zenphone') . ' — Numéros virtuels pour vos SMS de vérification')</title>
        <meta name="description" content="@yield('meta_description', "Achetez des numéros virtuels pour recevoir vos codes SMS WhatsApp, Google, Instagram, TikTok, Telegram et plus, payés en Mobile Money.")">

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
        <x-layout.header />

        <main>
            @yield('content')
        </main>

        <x-layout.footer />
    </body>
</html>
