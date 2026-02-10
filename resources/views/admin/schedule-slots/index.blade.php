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

            <section x-data="{ activeScope: 'current', activeDay: {{ $activeDay }} }" class="space-y-4">
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">Asignaciones por semana y dia</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Cambia entre semana actual y semana siguiente para planificar; los horarios se visualizan de lunes a sabado.
                </p>

                <div class="flex flex-wrap gap-2">
                    @foreach ($weekScopes as $scope)
                        <button
                            type="button"
                            x-on:click="activeScope = '{{ $scope['key'] }}'; activeDay = {{ $activeDay }};"
                            class="inline-flex items-center gap-2 rounded-xl border px-4 py-2 text-sm font-semibold transition focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-cyan-400 dark:focus:ring-offset-slate-900"
                            :class="activeScope === '{{ $scope['key'] }}'
                                ? 'border-cyan-500 bg-cyan-50 text-cyan-800 dark:border-cyan-400 dark:bg-cyan-500/15 dark:text-cyan-300'
                                : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                        >
                            <span>{{ $scope['label'] }}</span>
                            <span class="rounded-full bg-black/10 px-2 py-0.5 text-[10px] dark:bg-white/10">{{ $scope['range'] }}</span>
                        </button>
                    @endforeach
                </div>

                @foreach ($weekScopes as $scope)
                    @php
                        $scopeKey = $scope['key'];
                    @endphp

                    <div x-show="activeScope === '{{ $scopeKey }}'" x-cloak class="space-y-4">
                        <div class="flex flex-wrap gap-2">
                            @foreach ($scope['days'] as $day)
                                <button
                                    type="button"
                                    x-on:click="activeDay = {{ $day['day_of_week'] }}"
                                    class="inline-flex items-center gap-2 rounded-xl border px-3 py-2 text-xs font-semibold uppercase tracking-wide transition focus:outline-none focus:ring-2 focus:ring-cyan-500 focus:ring-offset-2 dark:focus:ring-cyan-400 dark:focus:ring-offset-slate-900"
                                    :class="activeDay === {{ $day['day_of_week'] }}
                                        ? 'border-slate-900 bg-slate-900 text-white dark:border-cyan-400 dark:bg-cyan-500 dark:text-slate-950'
                                        : 'border-slate-300 bg-white text-slate-700 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800'"
                                >
                                    <span>{{ $day['label'] }}</span>
                                    <span class="rounded-full bg-black/10 px-2 py-0.5 text-[10px] dark:bg-white/10">{{ $day['date'] }}</span>
                                </button>
                            @endforeach
                        </div>

                        @foreach ($scope['days'] as $day)
                            @php
                                $daySlots = $slotsByScope[$scopeKey][$day['day_of_week']] ?? collect();
                            @endphp

                            <div x-show="activeDay === {{ $day['day_of_week'] }}" x-cloak class="grid gap-4 lg:grid-cols-2">
                                @forelse ($daySlots as $slot)
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
                                        No hay horarios confirmados o cerrados para {{ strtolower($day['label']) }}.
                                    </div>
                                @endforelse
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>
