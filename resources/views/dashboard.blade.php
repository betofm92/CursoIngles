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
        <div x-data="{ activeDay: {{ $activeWeekDay }} }" class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            <section class="space-y-4">
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">Vista rapida de horarios</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach ($weekDays as $weekDay)
                        <button
                            type="button"
                            @click="activeDay = {{ $weekDay['day_of_week'] }}"
                            :class="activeDay === {{ $weekDay['day_of_week'] }} ? 'bg-slate-900 text-white dark:bg-cyan-500 dark:text-slate-950' : 'bg-white text-slate-700 hover:bg-slate-100 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                            class="inline-flex items-center gap-2 rounded-xl border border-slate-300 px-3 py-2 text-xs font-semibold uppercase tracking-wide transition dark:border-slate-700"
                        >
                            <span>{{ $weekDay['label'] }}</span>
                            <span class="rounded-full bg-black/10 px-2 py-0.5 text-[10px] dark:bg-white/10">{{ $weekDay['date'] }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($weekDays as $weekDay)
                    @php
                        $quickDaySlots = $weekCoursesByDay[$weekDay['day_of_week']] ?? collect();
                    @endphp

                    <div x-show="activeDay === {{ $weekDay['day_of_week'] }}" class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                        @forelse ($quickDaySlots as $slot)
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
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
