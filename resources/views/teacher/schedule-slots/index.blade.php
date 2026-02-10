<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-2xl font-bold text-slate-900 dark:text-slate-100">Confirmacion de Horarios Docentes</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">Crea borradores y confirma horarios para habilitar la asignacion de estudiantes por Admin.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            <section class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-slate-100">Nuevo horario</h3>
                <form method="POST" action="{{ route('teacher.schedule-slots.store') }}" class="mt-4 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @csrf

                    <div>
                        <label for="day_of_week" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Dia</label>
                        <select id="day_of_week" name="day_of_week" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                            @foreach ($dayOptions as $key => $label)
                                <option value="{{ $key }}" @selected((int) old('day_of_week', 1) === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="starts_at" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Hora inicio</label>
                        <input id="starts_at" name="starts_at" type="time" value="{{ old('starts_at', '08:00') }}" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                    </div>

                    <div>
                        <label for="ends_at" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Hora fin</label>
                        <input id="ends_at" name="ends_at" type="time" value="{{ old('ends_at', '10:00') }}" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                    </div>

                    <div>
                        <label for="classroom_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Aula</label>
                        <select id="classroom_id" name="classroom_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                            @foreach ($classrooms as $classroom)
                                <option value="{{ $classroom->id }}" @selected((int) old('classroom_id') === $classroom->id)>
                                    {{ $classroom->name }} ({{ min($classroom->capacity, 8) }} cupos)
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2">
                        <label for="course_topic_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tema del curso</label>
                        <select id="course_topic_id" name="course_topic_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                            @foreach ($topics->groupBy(fn ($topic) => $topic->course->name) as $courseName => $courseTopics)
                                <optgroup label="{{ $courseName }}">
                                    @foreach ($courseTopics as $topic)
                                        <option value="{{ $topic->id }}" @selected((int) old('course_topic_id') === $topic->id)>
                                            {{ $topic->title }}
                                        </option>
                                    @endforeach
                                </optgroup>
                            @endforeach
                        </select>
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <label for="notes" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Notas (opcional)</label>
                        <textarea id="notes" name="notes" rows="2" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">{{ old('notes') }}</textarea>
                    </div>

                    <div class="md:col-span-2 lg:col-span-3">
                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                            Guardar como borrador
                        </button>
                    </div>
                </form>
            </section>

            <section class="space-y-4">
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">Mis horarios (Cards)</h3>
                <div class="grid gap-4 lg:grid-cols-2">
                    @forelse ($slots as $slot)
                        @php $isDraft = $slot->status === \App\Models\ScheduleSlot::STATUS_DRAFT; @endphp
                        <article class="schedule-card rounded-3xl border border-slate-200 bg-white/95 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $slot->courseTopic->course->name }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300">{{ $slot->courseTopic->title }}</p>
                                </div>
                                <x-status-badge :status="$slot->status" />
                            </div>

                            <div class="space-y-1 text-sm text-slate-700 dark:text-slate-300">
                                <p><span class="font-semibold">Dia/Hora:</span> {{ $slot->day_label }} {{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}</p>
                                <p><span class="font-semibold">Aula:</span> {{ $slot->classroom->name }}</p>
                                <p><span class="font-semibold">Estudiantes asignados:</span> {{ $slot->enrollments_count }}/{{ $slot->capacity }}</p>
                                @if ($slot->notes)
                                    <p><span class="font-semibold">Nota:</span> {{ $slot->notes }}</p>
                                @endif
                            </div>

                            <div class="mt-4 flex flex-wrap items-center gap-2">
                                @if ($isDraft)
                                    <form method="POST" action="{{ route('teacher.schedule-slots.confirm', $slot) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="rounded-lg bg-emerald-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-emerald-500">
                                            Confirmar horario
                                        </button>
                                    </form>

                                    <form method="POST" action="{{ route('teacher.schedule-slots.destroy', $slot) }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-rose-500">
                                            Eliminar
                                        </button>
                                    </form>
                                @endif
                            </div>

                            @if ($isDraft)
                                <details class="mt-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                    <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-200">Editar borrador</summary>
                                    <form method="POST" action="{{ route('teacher.schedule-slots.update', $slot) }}" class="mt-4 grid gap-3 md:grid-cols-2">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Dia</label>
                                            <select name="day_of_week" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                                @foreach ($dayOptions as $key => $label)
                                                    <option value="{{ $key }}" @selected($slot->day_of_week === $key)>{{ $label }}</option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Aula</label>
                                            <select name="classroom_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                                @foreach ($classrooms as $classroom)
                                                    <option value="{{ $classroom->id }}" @selected($slot->classroom_id === $classroom->id)>
                                                        {{ $classroom->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Inicio</label>
                                            <input type="time" name="starts_at" value="{{ substr($slot->starts_at, 0, 5) }}" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Fin</label>
                                            <input type="time" name="ends_at" value="{{ substr($slot->ends_at, 0, 5) }}" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tema</label>
                                            <select name="course_topic_id" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                                @foreach ($topics->groupBy(fn ($topic) => $topic->course->name) as $courseName => $courseTopics)
                                                    <optgroup label="{{ $courseName }}">
                                                        @foreach ($courseTopics as $topic)
                                                            <option value="{{ $topic->id }}" @selected($slot->course_topic_id === $topic->id)>
                                                                {{ $topic->title }}
                                                            </option>
                                                        @endforeach
                                                    </optgroup>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="md:col-span-2">
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Notas</label>
                                            <textarea name="notes" rows="2" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">{{ $slot->notes }}</textarea>
                                        </div>

                                        <div class="md:col-span-2">
                                            <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                                                Guardar cambios
                                            </button>
                                        </div>
                                    </form>
                                </details>
                            @endif
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                            Aun no tienes horarios creados.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
