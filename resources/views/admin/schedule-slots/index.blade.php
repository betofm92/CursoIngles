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

            <section x-data="{ createOpen: false, activeScope: 'current', activeDay: {{ $activeDay }} }" class="relative space-y-4 overflow-visible">
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">Asignaciones por semana y dia</h3>
                <p class="text-sm text-slate-600 dark:text-slate-300">
                    Cambia entre semana actual y semana siguiente para planificar; los horarios se visualizan de lunes a sabado.
                </p>

                <div class="flex justify-start">
                    <button
                        type="button"
                        x-on:click="createOpen = !createOpen"
                        class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400"
                    >
                        Crear Curso
                    </button>
                </div>

                <section x-show="createOpen" x-cloak class="relative z-20 overflow-visible rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                    <h4 class="font-display text-lg font-semibold text-slate-900 dark:text-slate-100">Nuevo curso con horario</h4>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">
                        Completa los campos para crear el curso y dejar su horario confirmado.
                    </p>

                    @php
                        $teacherOptions = $teachers
                            ->map(fn ($teacher) => ['id' => $teacher->id, 'name' => $teacher->name, 'email' => $teacher->email])
                            ->values();
                        $studentOptions = $students
                            ->map(fn ($student) => ['id' => $student->id, 'name' => $student->name, 'email' => $student->email])
                            ->values();
                        $oldStudentIds = collect(old('student_ids', []))
                            ->map(fn ($id) => (int) $id)
                            ->filter()
                            ->values();
                    @endphp

                    <form method="POST" action="{{ route('admin.schedule-slots.courses.store') }}" x-data="courseCreatorForm({
                        teachers: @js($teacherOptions),
                        students: @js($studentOptions),
                        selectedTeacherId: @js(old('teacher_id') ? (int) old('teacher_id') : null),
                        selectedStudentIds: @js($oldStudentIds),
                    })" class="relative z-20 mt-4 grid gap-4 overflow-visible md:grid-cols-2 lg:grid-cols-3">
                        @csrf

                        <div>
                            <label for="course_code" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Codigo de curso</label>
                            <input id="course_code" name="course_code" type="text" value="{{ old('course_code') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                        </div>

                        <div>
                            <label for="course_name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nombre del curso</label>
                            <input id="course_name" name="course_name" type="text" value="{{ old('course_name') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                        </div>

                        <div class="relative z-30 overflow-visible md:col-span-2 lg:col-span-1" x-on:click.outside="teacherOpen = false">
                            <label for="teacher_search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Profesor</label>
                            <input id="teacher_search" type="text" x-model="teacherSearch" x-on:focus="teacherOpen = true" x-on:input="teacherOpen = true; selectedTeacherId = null" placeholder="Buscar profesor por nombre o email..." class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                            <input type="hidden" name="teacher_id" :value="selectedTeacherId">

                            <div x-show="teacherOpen" x-cloak class="absolute left-0 right-0 mt-2 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900" style="max-height: calc(3.5rem * 5 + 1rem); z-index: 2147483000;">
                                <template x-for="teacher in filteredTeachers()" :key="teacher.id">
                                    <button type="button" x-on:click="selectTeacher(teacher)" class="flex min-h-14 w-full items-start justify-between gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 dark:hover:bg-slate-800">
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="teacher.name"></p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="teacher.email"></p>
                                        </div>
                                        <span class="text-xs font-semibold uppercase tracking-wide text-cyan-700 dark:text-cyan-300">Seleccionar</span>
                                    </button>
                                </template>
                                <p x-show="filteredTeachers().length === 0" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
                                    Sin resultados.
                                </p>
                            </div>
                        </div>

                        <div>
                            <label for="topic_title" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Tema principal</label>
                            <input id="topic_title" name="topic_title" type="text" value="{{ old('topic_title') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                        </div>

                        <div>
                            <label for="day_of_week" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Dia</label>
                            <select id="day_of_week" name="day_of_week" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                @foreach ($dayOptions as $value => $label)
                                    <option value="{{ $value }}" @selected((int) old('day_of_week', 1) === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="classroom_id" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Aula</label>
                            <select id="classroom_id" name="classroom_id" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                @foreach ($classrooms as $classroom)
                                    <option value="{{ $classroom->id }}" @selected((int) old('classroom_id') === $classroom->id)>
                                        {{ $classroom->name }} ({{ min($classroom->capacity, 8) }} cupos)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label for="starts_at" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Hora inicio</label>
                            <input id="starts_at" name="starts_at" type="time" min="08:00" max="19:59" value="{{ old('starts_at', '08:00') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                        </div>

                        <div>
                            <label for="ends_at" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Hora fin</label>
                            <input id="ends_at" name="ends_at" type="time" min="08:01" max="20:00" value="{{ old('ends_at', '10:00') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                        </div>

                        <div class="relative z-30 overflow-visible md:col-span-2 lg:col-span-3" x-on:click.outside="studentOpen = false">
                            <label for="student_search" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Estudiantes iniciales (opcional)</label>
                            <input id="student_search" type="text" x-model="studentSearch" x-on:focus="studentOpen = true" x-on:input="studentOpen = true" placeholder="Buscar estudiante por nombre o email..." class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">

                            <div x-show="studentOpen" x-cloak class="absolute left-0 right-0 mt-2 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900" style="max-height: calc(3.5rem * 8 + 1rem); z-index: 2147483000;">
                                <template x-for="student in filteredStudents()" :key="student.id">
                                    <button type="button" x-on:click="toggleStudent(student.id)" class="flex min-h-14 w-full items-start justify-between gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 dark:hover:bg-slate-800">
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="student.name"></p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="student.email"></p>
                                        </div>
                                        <span class="text-xs font-semibold uppercase tracking-wide" :class="isStudentSelected(student.id) ? 'text-emerald-700 dark:text-emerald-300' : 'text-cyan-700 dark:text-cyan-300'" x-text="isStudentSelected(student.id) ? 'Seleccionado' : 'Agregar'"></span>
                                    </button>
                                </template>
                                <p x-show="filteredStudents().length === 0" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
                                    Sin resultados.
                                </p>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="student in selectedStudents()" :key="`selected-${student.id}`">
                                    <span class="inline-flex items-center gap-2 rounded-full bg-cyan-100 px-3 py-1 text-xs font-semibold text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300">
                                        <span x-text="student.name"></span>
                                        <button type="button" x-on:click="removeStudent(student.id)" class="rounded-full bg-cyan-200 px-1.5 py-0.5 text-[10px] leading-none text-cyan-900 dark:bg-cyan-400/30 dark:text-cyan-100">
                                            x
                                        </button>
                                    </span>
                                </template>
                                <p x-show="selectedStudentIds.length === 0" class="text-xs text-slate-500 dark:text-slate-400">
                                    Sin estudiantes seleccionados.
                                </p>
                            </div>

                            <template x-for="studentId in selectedStudentIds" :key="`student-input-${studentId}`">
                                <input type="hidden" name="student_ids[]" :value="studentId">
                            </template>
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label for="course_description" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Descripcion del curso (opcional)</label>
                            <textarea id="course_description" name="course_description" rows="2" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">{{ old('course_description') }}</textarea>
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label for="topic_description" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Descripcion del tema (opcional)</label>
                            <textarea id="topic_description" name="topic_description" rows="2" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">{{ old('topic_description') }}</textarea>
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <label for="notes" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Notas del horario (opcional)</label>
                            <textarea id="notes" name="notes" rows="2" class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">{{ old('notes') }}</textarea>
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                                Guardar curso
                            </button>
                        </div>
                    </form>
                </section>

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

    <script>
        function courseCreatorForm(config) {
            return {
                teachers: config.teachers ?? [],
                students: config.students ?? [],
                selectedTeacherId: config.selectedTeacherId ?? null,
                selectedStudentIds: (config.selectedStudentIds ?? []).map((id) => Number(id)),
                teacherSearch: '',
                studentSearch: '',
                teacherOpen: false,
                studentOpen: false,

                init() {
                    if (this.selectedTeacherId) {
                        const selectedTeacher = this.teachers.find((teacher) => teacher.id === Number(this.selectedTeacherId));
                        if (selectedTeacher) {
                            this.teacherSearch = `${selectedTeacher.name} - ${selectedTeacher.email}`;
                        }
                    }
                },

                filteredTeachers() {
                    const query = this.teacherSearch.trim().toLowerCase();
                    if (!query) {
                        return this.teachers;
                    }

                    return this.teachers
                        .filter((teacher) => `${teacher.name} ${teacher.email}`.toLowerCase().includes(query));
                },

                selectTeacher(teacher) {
                    this.selectedTeacherId = Number(teacher.id);
                    this.teacherSearch = `${teacher.name} - ${teacher.email}`;
                    this.teacherOpen = false;
                },

                filteredStudents() {
                    const query = this.studentSearch.trim().toLowerCase();
                    if (!query) {
                        return this.students;
                    }

                    return this.students
                        .filter((student) => `${student.name} ${student.email}`.toLowerCase().includes(query));
                },

                isStudentSelected(studentId) {
                    return this.selectedStudentIds.includes(Number(studentId));
                },

                toggleStudent(studentId) {
                    const id = Number(studentId);
                    if (this.isStudentSelected(id)) {
                        this.selectedStudentIds = this.selectedStudentIds.filter((selectedId) => selectedId !== id);
                        return;
                    }

                    this.selectedStudentIds.push(id);
                },

                removeStudent(studentId) {
                    const id = Number(studentId);
                    this.selectedStudentIds = this.selectedStudentIds.filter((selectedId) => selectedId !== id);
                },

                selectedStudents() {
                    return this.students.filter((student) => this.isStudentSelected(student.id));
                },
            };
        }
    </script>
</x-app-layout>
