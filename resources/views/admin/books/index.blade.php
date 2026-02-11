<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-2xl font-bold text-slate-900 dark:text-slate-100">Libros</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">CRUD de libros con asignacion dinamica de estudiantes.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            @php
                $studentOptions = $students
                    ->map(fn ($student) => ['id' => $student->id, 'name' => $student->name, 'email' => $student->email])
                    ->values();
                $oldStudentIds = collect(old('student_ids', []))
                    ->map(fn ($id) => (int) $id)
                    ->filter()
                    ->values();
            @endphp

            <section class="rounded-3xl border border-slate-200 bg-white/95 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                <h3 class="font-display text-lg font-semibold text-slate-900 dark:text-slate-100">Crear libro</h3>

                <div x-data="bookStudentsSelector({ students: @js($studentOptions), selectedStudentIds: @js($oldStudentIds) })" class="mt-4">
                    <form method="POST" action="{{ route('admin.books.store') }}" class="grid gap-4 overflow-visible md:grid-cols-2 lg:grid-cols-3">
                        @csrf

                        <div>
                            <label for="book_code" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Codigo</label>
                            <input id="book_code" name="code" type="text" value="{{ old('code') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                        </div>

                        <div>
                            <label for="book_name" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nombre</label>
                            <input id="book_name" name="name" type="text" value="{{ old('name') }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                        </div>

                        <div class="relative z-30 overflow-visible md:col-span-2 lg:col-span-3" x-on:click.outside="open = false">
                            <label for="student_search_create" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Estudiantes</label>
                            <input id="student_search_create" type="text" x-model="search" x-on:focus="open = true" x-on:input="open = true" placeholder="Buscar estudiante por nombre o email..." class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">

                            <div x-show="open" x-cloak class="absolute left-0 right-0 mt-2 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900" style="max-height: calc(3.5rem * 8 + 1rem); z-index: 2147483000;">
                                <template x-for="student in filteredStudents()" :key="student.id">
                                    <button type="button" x-on:click="toggleStudent(student.id)" class="flex min-h-14 w-full items-start justify-between gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 dark:hover:bg-slate-800">
                                        <div>
                                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="student.name"></p>
                                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="student.email"></p>
                                        </div>
                                        <span class="text-xs font-semibold uppercase tracking-wide" :class="isSelected(student.id) ? 'text-emerald-700 dark:text-emerald-300' : 'text-cyan-700 dark:text-cyan-300'" x-text="isSelected(student.id) ? 'Seleccionado' : 'Agregar'"></span>
                                    </button>
                                </template>
                                <p x-show="filteredStudents().length === 0" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
                                    Sin resultados.
                                </p>
                            </div>

                            <div class="mt-2 flex flex-wrap gap-2">
                                <template x-for="student in selectedStudents()" :key="`selected-create-${student.id}`">
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

                            <template x-for="studentId in selectedStudentIds" :key="`create-student-${studentId}`">
                                <input type="hidden" name="student_ids[]" :value="studentId">
                            </template>
                        </div>

                        <div class="md:col-span-2 lg:col-span-3">
                            <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                                Guardar libro
                            </button>
                        </div>
                    </form>
                </div>
            </section>

            <section class="space-y-4">
                <h3 class="font-display text-xl font-semibold text-slate-900 dark:text-slate-100">Listado de libros</h3>

                <div class="grid gap-4 lg:grid-cols-2">
                    @forelse ($books as $book)
                        <article class="schedule-card rounded-3xl border border-slate-200 bg-white/95 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                            <div class="mb-3 flex items-start justify-between gap-3">
                                <div>
                                    <p class="text-base font-semibold text-slate-900 dark:text-slate-100">{{ $book->name }}</p>
                                    <p class="text-sm text-slate-600 dark:text-slate-300">Codigo: {{ $book->code }}</p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-cyan-100 px-2.5 py-1 text-xs font-semibold uppercase tracking-wide text-cyan-800 dark:bg-cyan-500/20 dark:text-cyan-300">
                                    {{ $book->students_count }} estudiantes
                                </span>
                            </div>

                            <div class="mb-4 space-y-1 text-sm text-slate-700 dark:text-slate-300">
                                <p class="font-semibold">Estudiantes asignados:</p>
                                @forelse ($book->students as $student)
                                    <p>{{ $student->name }} <span class="text-xs text-slate-500 dark:text-slate-400">({{ $student->email }})</span></p>
                                @empty
                                    <p class="text-slate-500 dark:text-slate-400">Sin estudiantes asignados.</p>
                                @endforelse
                            </div>

                            <details class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/70">
                                <summary class="cursor-pointer text-sm font-semibold text-slate-800 dark:text-slate-200">Editar libro</summary>

                                <div x-data="bookStudentsSelector({ students: @js($studentOptions), selectedStudentIds: @js($book->students->pluck('id')->values()) })" class="mt-4">
                                    <form method="POST" action="{{ route('admin.books.update', $book) }}" class="grid gap-3 overflow-visible md:grid-cols-2">
                                        @csrf
                                        @method('PUT')

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Codigo</label>
                                            <input name="code" type="text" value="{{ $book->code }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                        </div>

                                        <div>
                                            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Nombre</label>
                                            <input name="name" type="text" value="{{ $book->name }}" required class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                        </div>

                                        <div class="relative z-30 overflow-visible md:col-span-2" x-on:click.outside="open = false">
                                            <label for="student_search_edit_{{ $book->id }}" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Estudiantes</label>
                                            <input id="student_search_edit_{{ $book->id }}" type="text" x-model="search" x-on:focus="open = true" x-on:input="open = true" placeholder="Buscar estudiante por nombre o email..." class="w-full rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">

                                            <div x-show="open" x-cloak class="absolute left-0 right-0 mt-2 w-full overflow-y-auto rounded-xl border border-slate-200 bg-white p-2 shadow-lg dark:border-slate-700 dark:bg-slate-900" style="max-height: calc(3.5rem * 8 + 1rem); z-index: 2147483000;">
                                                <template x-for="student in filteredStudents()" :key="`edit-${student.id}`">
                                                    <button type="button" x-on:click="toggleStudent(student.id)" class="flex min-h-14 w-full items-start justify-between gap-2 rounded-lg px-3 py-2 text-left transition hover:bg-slate-100 dark:hover:bg-slate-800">
                                                        <div>
                                                            <p class="text-sm font-medium text-slate-900 dark:text-slate-100" x-text="student.name"></p>
                                                            <p class="text-xs text-slate-500 dark:text-slate-400" x-text="student.email"></p>
                                                        </div>
                                                        <span class="text-xs font-semibold uppercase tracking-wide" :class="isSelected(student.id) ? 'text-emerald-700 dark:text-emerald-300' : 'text-cyan-700 dark:text-cyan-300'" x-text="isSelected(student.id) ? 'Seleccionado' : 'Agregar'"></span>
                                                    </button>
                                                </template>
                                                <p x-show="filteredStudents().length === 0" class="px-3 py-2 text-xs text-slate-500 dark:text-slate-400">
                                                    Sin resultados.
                                                </p>
                                            </div>

                                            <div class="mt-2 flex flex-wrap gap-2">
                                                <template x-for="student in selectedStudents()" :key="`selected-edit-{{ $book->id }}-${student.id}`">
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

                                            <template x-for="studentId in selectedStudentIds" :key="`edit-student-{{ $book->id }}-${studentId}`">
                                                <input type="hidden" name="student_ids[]" :value="studentId">
                                            </template>
                                        </div>

                                        <div class="md:col-span-2 flex flex-wrap items-center gap-2">
                                            <button type="submit" class="rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                                                Guardar cambios
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </details>

                            <form method="POST" action="{{ route('admin.books.destroy', $book) }}" class="mt-4">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="rounded-lg bg-rose-600 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-rose-500">
                                    Eliminar libro
                                </button>
                            </form>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                            No hay libros registrados.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>

    <script>
        function bookStudentsSelector(config) {
            return {
                students: config.students ?? [],
                selectedStudentIds: (config.selectedStudentIds ?? []).map((id) => Number(id)),
                search: '',
                open: false,

                filteredStudents() {
                    const query = this.search.trim().toLowerCase();
                    if (!query) {
                        return this.students;
                    }

                    return this.students.filter((student) => `${student.name} ${student.email}`.toLowerCase().includes(query));
                },

                isSelected(studentId) {
                    return this.selectedStudentIds.includes(Number(studentId));
                },

                toggleStudent(studentId) {
                    const id = Number(studentId);
                    if (this.isSelected(id)) {
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
                    return this.students.filter((student) => this.isSelected(student.id));
                },
            };
        }
    </script>
</x-app-layout>
