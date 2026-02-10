<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold leading-tight text-slate-900 dark:text-slate-100">
                    Panel de Control
                </h2>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Gestion rapida de cursos, horarios y asignaciones por rol.
                </p>
            </div>
            <div class="rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300">
                Rol: {{ $role }}
            </div>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            @if ($role === 'admin')
                <div class="grid gap-4 md:grid-cols-4">
                    <div class="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Profesores</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['professors'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Estudiantes</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['students'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-5 shadow-sm dark:border-emerald-500/40 dark:bg-emerald-500/10">
                        <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Horarios Confirmados</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-800 dark:text-emerald-200">{{ $stats['confirmed_slots'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-5 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/10">
                        <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">Pendientes</p>
                        <p class="mt-2 text-3xl font-bold text-amber-800 dark:text-amber-200">{{ $stats['pending_slots'] }}</p>
                    </div>
                </div>

                <a href="{{ route('admin.schedule-slots.index') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                    Ir a asignaciones de estudiantes
                </a>
            @elseif ($role === 'profesor')
                <div class="grid gap-4 md:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                        <p class="text-xs uppercase tracking-wide text-slate-500 dark:text-slate-400">Total Horarios</p>
                        <p class="mt-2 text-3xl font-bold text-slate-900 dark:text-slate-100">{{ $stats['total_slots'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-amber-200 bg-amber-50/90 p-5 shadow-sm dark:border-amber-500/40 dark:bg-amber-500/10">
                        <p class="text-xs uppercase tracking-wide text-amber-700 dark:text-amber-300">Borradores</p>
                        <p class="mt-2 text-3xl font-bold text-amber-800 dark:text-amber-200">{{ $stats['draft_slots'] }}</p>
                    </div>
                    <div class="rounded-2xl border border-emerald-200 bg-emerald-50/90 p-5 shadow-sm dark:border-emerald-500/40 dark:bg-emerald-500/10">
                        <p class="text-xs uppercase tracking-wide text-emerald-700 dark:text-emerald-300">Confirmados</p>
                        <p class="mt-2 text-3xl font-bold text-emerald-800 dark:text-emerald-200">{{ $stats['confirmed_slots'] }}</p>
                    </div>
                </div>

                <a href="{{ route('teacher.schedule-slots.index') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                    Administrar mis horarios
                </a>
            @else
                <div class="grid gap-4 md:grid-cols-1">
                    <div class="rounded-2xl border border-cyan-200 bg-cyan-50/90 p-5 shadow-sm dark:border-cyan-500/40 dark:bg-cyan-500/10">
                        <p class="text-xs uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Clases Asignadas</p>
                        <p class="mt-2 text-3xl font-bold text-cyan-800 dark:text-cyan-200">{{ $stats['assigned_slots'] }}</p>
                    </div>
                </div>

                <a href="{{ route('student.schedule-slots.index') }}" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                    Ver mis clases
                </a>
            @endif

            <section class="space-y-4">
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">Vista rapida de horarios</h3>
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @forelse ($slots as $slot)
                        <article class="schedule-card rounded-2xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $slot->courseTopic->course->name }}</p>
                                    <p class="text-xs text-slate-500 dark:text-slate-400">{{ $slot->courseTopic->title }}</p>
                                </div>
                                <x-status-badge :status="$slot->status" />
                            </div>

                            <div class="space-y-1 text-sm text-slate-700 dark:text-slate-300">
                                <p><span class="font-semibold">Horario:</span> {{ $slot->day_label }} {{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}</p>
                                <p><span class="font-semibold">Aula:</span> {{ $slot->classroom->name }}</p>
                                @if ($role !== 'profesor')
                                    <p><span class="font-semibold">Profesor:</span> {{ $slot->teacher->name }}</p>
                                @endif
                                <p><span class="font-semibold">Cupos:</span> {{ $slot->enrollments_count }}/{{ $slot->capacity }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                            No hay horarios para mostrar.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
