<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-2xl font-bold text-slate-900 dark:text-slate-100">Mis Clases Asignadas</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">Consulta tus horarios confirmados por el instituto.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            <section class="space-y-4">
                <div class="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @forelse ($slots as $slot)
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
                                <p><span class="font-semibold">Grupo:</span> {{ $slot->enrollments_count }}/{{ $slot->capacity }}</p>
                            </div>
                        </article>
                    @empty
                        <div class="rounded-2xl border border-dashed border-slate-300 bg-white/70 p-6 text-sm text-slate-600 dark:border-slate-700 dark:bg-slate-900/70 dark:text-slate-300">
                            Todavia no tienes clases asignadas.
                        </div>
                    @endforelse
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
