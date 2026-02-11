<nav x-data="{ open: false }" class="border-b border-slate-200/80 bg-white/90 backdrop-blur dark:border-slate-800/80 dark:bg-slate-900/80">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <div class="shrink-0 flex items-center">
                    <a href="{{ route('dashboard') }}" class="font-display text-lg font-bold tracking-tight text-slate-900 dark:text-slate-100">
                        CursoIngles
                    </a>
                </div>

                <div class="hidden space-x-6 sm:-my-px sm:ms-10 sm:flex">
                    <x-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                        Dashboard
                    </x-nav-link>

                    @if (Auth::user()->hasRole('admin'))
                        <x-nav-link :href="route('admin.schedule-slots.index')" :active="request()->routeIs('admin.schedule-slots.*')">
                            Asignaciones
                        </x-nav-link>
                        <x-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                            Usuarios
                        </x-nav-link>
                        <x-nav-link :href="route('admin.books.index')" :active="request()->routeIs('admin.books.*')">
                            Libros
                        </x-nav-link>
                        <x-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">
                            Reportes
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->hasRole('profesor'))
                        <x-nav-link :href="route('teacher.schedule-slots.index')" :active="request()->routeIs('teacher.schedule-slots.*')">
                            Mis Horarios
                        </x-nav-link>
                    @endif

                    @if (Auth::user()->hasRole('estudiante'))
                        <x-nav-link :href="route('student.schedule-slots.index')" :active="request()->routeIs('student.schedule-slots.*')">
                            Mis Clases
                        </x-nav-link>
                    @endif
                </div>
            </div>

            <div class="hidden sm:flex sm:items-center sm:ms-6 sm:gap-3">
                <x-theme-toggle />

                <x-dropdown align="right" width="52">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center gap-2 rounded-lg border border-slate-200 bg-white px-3 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50 focus:outline-none dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            <span>{{ Auth::user()->name }}</span>
                            <span class="rounded-full bg-cyan-100 px-2 py-0.5 text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300">
                                {{ Auth::user()->roles->first()?->name ?? 'sin-rol' }}
                            </span>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            Perfil
                        </x-dropdown-link>

                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                                Cerrar sesion
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
            </div>

            <div class="-me-2 flex items-center gap-2 sm:hidden">
                <x-theme-toggle />

                <button @click="open = ! open" class="inline-flex items-center justify-center rounded-md p-2 text-slate-500 transition hover:bg-slate-100 hover:text-slate-700 focus:outline-none dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-slate-200">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{ 'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{ 'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <div :class="{ 'block': open, 'hidden': ! open }" class="hidden sm:hidden">
        <div class="space-y-1 pb-3 pt-2">
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                Dashboard
            </x-responsive-nav-link>

            @if (Auth::user()->hasRole('admin'))
                <x-responsive-nav-link :href="route('admin.schedule-slots.index')" :active="request()->routeIs('admin.schedule-slots.*')">
                    Asignaciones
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.users.index')" :active="request()->routeIs('admin.users.*')">
                    Usuarios
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.books.index')" :active="request()->routeIs('admin.books.*')">
                    Libros
                </x-responsive-nav-link>
                <x-responsive-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.reports.*')">
                    Reportes
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->hasRole('profesor'))
                <x-responsive-nav-link :href="route('teacher.schedule-slots.index')" :active="request()->routeIs('teacher.schedule-slots.*')">
                    Mis Horarios
                </x-responsive-nav-link>
            @endif

            @if (Auth::user()->hasRole('estudiante'))
                <x-responsive-nav-link :href="route('student.schedule-slots.index')" :active="request()->routeIs('student.schedule-slots.*')">
                    Mis Clases
                </x-responsive-nav-link>
            @endif
        </div>

        <div class="border-t border-slate-200 pb-1 pt-4 dark:border-slate-800">
            <div class="px-4">
                <div class="text-base font-medium text-slate-800 dark:text-slate-100">{{ Auth::user()->name }}</div>
                <div class="text-sm font-medium text-slate-500 dark:text-slate-400">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    Perfil
                </x-responsive-nav-link>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault(); this.closest('form').submit();">
                        Cerrar sesion
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
    </div>
</nav>
