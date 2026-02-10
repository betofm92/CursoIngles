<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>
        <x-theme-init-script />

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[radial-gradient(circle_at_top,_#ffedd5,_#f8fafc_35%,_#e0f2fe_100%)] font-sans antialiased text-slate-900 dark:bg-[radial-gradient(circle_at_top,_#082f49,_#020617_38%,_#0f172a_100%)] dark:text-slate-100">
        <div class="fixed right-4 top-4 z-20">
            <x-theme-toggle />
        </div>

        <div class="flex min-h-screen flex-col items-center justify-center pt-6 sm:pt-0">
            <div>
                <a href="/" class="font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-100">
                    CursoIngles
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden rounded-3xl border border-slate-200 bg-white/95 px-6 py-5 shadow-xl dark:border-slate-800 dark:bg-slate-900/90 sm:max-w-md">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
