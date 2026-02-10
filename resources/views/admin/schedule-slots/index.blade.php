<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-2xl font-bold text-slate-900 dark:text-slate-100">Asignacion de Estudiantes</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">Admin asigna estudiantes solo en horarios confirmados por profesores.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            <section class="space-y-4">
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">Horarios confirmados y cerrados</h3>
                <div class="grid gap-4 lg:grid-cols-2">
                    @forelse ($slots as $slot)
                        @php
                            $isConfirmed = $slot->status === \App\Models\ScheduleSlot::STATUS_CONFIRMED;
                            $canAssign = $isConfirmed && $slot->available_seats > 0;
                        @endphp
                        <article class="schedule-card rounded-3xl border border-slate-200 bg-white/95 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $slot->courseTopic->course->name }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $slot->courseTopic->title }}</p>
                                </div>
                                <x-status-badge :status="$slot->status" />
                            </div>

                            <div class="space-y-1 text-sm text-slate-700 dark:text-slate-300">
                                <p><span class="font-semibold">Profesor:</span> {{ $slot->teacher->name }}</p>
                                <p><span class="font-semibold">Horario:</span> {{ $slot->day_label }} {{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}</p>
                                <p><span class="font-semibold">Aula:</span> {{ $slot->classroom->name }}</p>
                                <p><span class="font-semibold">Cupos:</span> {{ $slot->enrollments_count }}/{{ $slot->capacity }}</p>
                            </div>

                            @if ($isConfirmed)
                                <form method="POST" action="{{ route('admin.schedule-slots.close', $slot) }}" class="mt-4">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                        Cerrar horario
                                    </button>
                                </form>
                            @endif

                            <div class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                <h4 class="text-sm font-semibold text-slate-800 dark:text-slate-200">Estudiantes asignados</h4>
                                <div class="mt-3 space-y-2">
                                    @forelse ($slot->enrollments as $enrollment)
                                        <div class="flex items-center justify-between gap-2 rounded-xl border border-slate-200 bg-white px-3 py-2 dark:border-slate-700 dark:bg-slate-900/80">
                                            <div>
                                                <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $enrollment->student->name }}</p>
                                                <p class="text-xs text-slate-500 dark:text-slate-400">{{ $enrollment->student->email }}</p>
                                            </div>
                                            <form method="POST" action="{{ route('admin.schedule-slots.enrollments.destroy', [$slot, $enrollment]) }}">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-md bg-rose-100 px-2.5 py-1.5 text-xs font-semibold uppercase tracking-wide text-rose-700 transition hover:bg-rose-200 dark:bg-rose-500/20 dark:text-rose-300 dark:hover:bg-rose-500/30">
                                                    Quitar
                                                </button>
                                            </form>
                                        </div>
                                    @empty
                                        <p class="text-sm text-slate-500 dark:text-slate-400">Sin estudiantes asignados.</p>
                                    @endforelse
                                </div>
                            </div>

                            @if ($isConfirmed)
                                <form method="POST" action="{{ route('admin.schedule-slots.enrollments.store', $slot) }}" class="mt-4 flex flex-col gap-3 sm:flex-row">
                                    @csrf
                                    <select name="student_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400" @disabled(! $canAssign)>
                                        @foreach ($students as $student)
                                            <option value="{{ $student->id }}">{{ $student->name }} - {{ $student->email }}</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="rounded-xl bg-slate-900 px-4 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800 disabled:cursor-not-allowed disabled:bg-slate-400 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400 dark:disabled:bg-slate-600 dark:disabled:text-slate-300" @disabled(! $canAssign)>
                                        Asignar
                                    </button>
                                </form>
                                @unless ($canAssign)
                                    <p class="mt-2 text-xs font-medium text-amber-700 dark:text-amber-300">Sin cupos disponibles (maximo 8 por aula).</p>
                                @endunless
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                            No hay horarios confirmados aun. Espera la confirmacion de los profesores.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
