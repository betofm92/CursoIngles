<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-2xl font-bold text-slate-900 dark:text-slate-100">Gestion de Usuarios</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">El administrador puede crear, editar y eliminar profesores y estudiantes.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            <section class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-slate-100">Crear usuario</h3>

                <form method="POST" action="{{ route('admin.users.store') }}" class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @csrf

                    <div>
                        <label for="name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nombre</label>
                        <input id="name" name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                    </div>

                    <div>
                        <label for="email" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email</label>
                        <input id="email" name="email" type="email" value="{{ old('email') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                    </div>

                    <div>
                        <label for="role" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Rol</label>
                        <select id="role" name="role" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                            @foreach ($roleOptions as $roleValue => $roleLabel)
                                <option value="{{ $roleValue }}" @selected(old('role', 'estudiante') === $roleValue)>{{ $roleLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="password" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Contrasena</label>
                        <input id="password" name="password" type="password" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                    </div>

                    <div>
                        <label for="password_confirmation" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Confirmar contrasena</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                            Crear usuario
                        </button>
                    </div>
                </form>
            </section>

            @php
                $usersByRole = $managedUsers->groupBy(fn ($user) => $user->roles->first()?->name ?? 'sin_rol');
                $roleTabs = [
                    'profesor' => [
                        'title' => 'Profesores',
                        'empty' => 'No hay profesores registrados.',
                    ],
                    'estudiante' => [
                        'title' => 'Estudiantes',
                        'empty' => 'No hay estudiantes registrados.',
                    ],
                ];
                $defaultTab = old('role', 'profesor');
                if (! array_key_exists($defaultTab, $roleTabs)) {
                    $defaultTab = 'profesor';
                }
            @endphp

            <section x-data="{ activeTab: '{{ $defaultTab }}' }" class="space-y-4">
                <div class="flex flex-wrap items-center gap-2">
                    @foreach ($roleTabs as $roleValue => $tabConfig)
                        <button
                            type="button"
                            x-on:click="activeTab = '{{ $roleValue }}'"
                            class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-cyan-400 dark:focus:ring-offset-slate-900"
                            :class="activeTab === '{{ $roleValue }}'
                                ? 'border-cyan-500 bg-cyan-50 text-cyan-800 dark:border-cyan-400 dark:bg-cyan-500/15 dark:text-cyan-300'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                        >
                            <span>{{ $tabConfig['title'] }}</span>
                            <span class="rounded-full bg-slate-200 px-2 py-0.5 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                {{ $usersByRole->get($roleValue, collect())->count() }}
                            </span>
                        </button>
                    @endforeach
                </div>

                @foreach ($roleTabs as $roleValue => $tabConfig)
                    <div x-show="activeTab === '{{ $roleValue }}'" x-cloak class="space-y-4">
                        <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $tabConfig['title'] }}</h3>

                        <div class="grid gap-4 lg:grid-cols-2">
                            @forelse ($usersByRole->get($roleValue, collect()) as $managedUser)
                                <article class="schedule-card rounded-3xl border border-slate-200 bg-white/95 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                                    <div class="mb-3 flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $managedUser->name }}</p>
                                            <p class="text-sm text-slate-600 dark:text-slate-300">{{ $managedUser->email }}</p>
                                        </div>
                                        <span class="inline-flex items-center rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300">
                                            {{ $roleOptions[$roleValue] ?? ucfirst($roleValue) }}
                                        </span>
                                    </div>

                                    <p class="text-xs text-slate-500 dark:text-slate-400">
                                        Creado: {{ $managedUser->created_at?->format('Y-m-d H:i') }}
                                    </p>

                                    <details class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                        <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-200">Editar usuario</summary>

                                        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="mt-4 grid gap-3 md:grid-cols-2">
                                            @csrf
                                            @method('PUT')

                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nombre</label>
                                                <input name="name" type="text" value="{{ $managedUser->name }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Email</label>
                                                <input name="email" type="email" value="{{ $managedUser->email }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Rol</label>
                                                <select name="role" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                                    @foreach ($roleOptions as $roleOptionValue => $roleOptionLabel)
                                                        <option value="{{ $roleOptionValue }}" @selected($managedUser->hasRole($roleOptionValue))>{{ $roleOptionLabel }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div>
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nueva contrasena (opcional)</label>
                                                <input name="password" type="password" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                            </div>

                                            <div class="md:col-span-2">
                                                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Confirmar nueva contrasena</label>
                                                <input name="password_confirmation" type="password" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                            </div>

                                            <div class="md:col-span-2 flex flex-wrap items-center gap-2">
                                                <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                                                    Guardar cambios
                                                </button>
                                            </div>
                                        </form>
                                    </details>

                                    <form method="POST" action="{{ route('admin.users.destroy', $managedUser) }}" class="mt-4">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-rose-500">
                                            Eliminar usuario
                                        </button>
                                    </form>
                                </article>
                            @empty
                                <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                                    {{ $tabConfig['empty'] }}
                                </div>
                            @endforelse
                        </div>
                    </div>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
