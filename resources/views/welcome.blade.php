<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ config('app.name', 'CursoIngles') }}</title>
        <x-theme-init-script />

        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Sora:wght@400;500;600;700&family=Space+Grotesk:wght@400;500;600;700&display=swap" rel="stylesheet">
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="bg-[radial-gradient(circle_at_top,_#ffedd5,_#f8fafc_35%,_#e0f2fe_100%)] font-sans antialiased text-slate-900 dark:bg-[radial-gradient(circle_at_top,_#082f49,_#020617_38%,_#0f172a_100%)] dark:text-slate-100">
        <div class="fixed right-4 top-4 z-20">
            <x-theme-toggle />
        </div>

        <main class="mx-auto flex min-h-screen w-full max-w-7xl items-center px-4 py-12 sm:px-6 lg:px-8">
            <section class="w-full rounded-[2rem] border border-slate-200 bg-white/90 p-10 shadow-xl backdrop-blur dark:border-slate-800 dark:bg-slate-900/85">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-cyan-700 dark:text-cyan-300">Instituto de Idiomas</p>
                <h1 class="mt-3 max-w-3xl font-display text-4xl font-bold leading-tight text-slate-900 dark:text-slate-100 sm:text-5xl">
                    Plataforma de gestion de cursos de ingles con horarios en cards.
                </h1>
                <p class="mt-4 max-w-2xl text-base text-slate-600 dark:text-slate-300">
                    Profesores confirman sus horarios, Admin asigna estudiantes y cada rol trabaja en su propio panel.
                </p>

                <div class="mt-8 flex flex-wrap gap-3">
                    @auth
                        <a href="{{ route('dashboard') }}" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                            Ir al Dashboard
                        </a>
                    @else
                        <a href="{{ route('login') }}" class="rounded-xl bg-slate-900 px-5 py-3 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                            Iniciar sesion
                        </a>
                        <a href="{{ route('register') }}" class="rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-800 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            Crear cuenta
                        </a>
                    @endauth
                </div>
            </section>
        </main>
    </body>
</html>
