<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="font-display text-2xl font-bold text-slate-900 dark:text-slate-100">Reportes</h2>
            <p class="text-sm text-slate-600 dark:text-slate-300">Genera reportes semanales en PDF, CSV o XLSX.</p>
        </div>
    </x-slot>

    <div class="py-10">
        <div class="mx-auto max-w-7xl space-y-6 px-4 sm:px-6 lg:px-8">
            <x-flash-messages />

            <section x-data="{ activeScope: '{{ $activeScope }}' }" class="space-y-4">
                <div class="flex flex-wrap items-end justify-between gap-3 rounded-3xl border border-slate-200 bg-white/95 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                    <form method="GET" action="{{ route('admin.reports.index') }}" class="flex flex-wrap items-end gap-3">
                        <input type="hidden" name="week_scope" :value="activeScope">

                        <div>
                            <label for="status" class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Estado</label>
                            <select id="status" name="status" class="w-44 rounded-xl border-slate-300 bg-white text-sm text-slate-900 shadow-sm focus:border-cyan-500 focus:ring-cyan-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:focus:border-cyan-400 dark:focus:ring-cyan-400">
                                @foreach ($statusOptions as $value => $label)
                                    <option value="{{ $value }}" @selected($statusFilter === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="inline-flex items-center rounded-xl bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                            Generar reporte
                        </button>
                    </form>

                    <div class="flex flex-wrap gap-2">
                        @foreach ($weekScopes as $scope)
                            <button
                                type="button"
                                x-on:click="activeScope = '{{ $scope['key'] }}'"
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
                </div>

                @foreach ($weekScopes as $scope)
                    @php
                        $scopeKey = $scope['key'];
                    @endphp

                    <div x-show="activeScope === '{{ $scopeKey }}'" x-cloak class="space-y-4">
                        <div class="flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 dark:border-slate-800 dark:bg-slate-900/80">
                            <div>
                                <p class="text-sm font-semibold text-slate-900 dark:text-slate-100">{{ $scope['label'] }}</p>
                                <p class="text-xs text-slate-600 dark:text-slate-400">{{ $scope['range'] }}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">
                                <a href="{{ route('admin.reports.download', ['week_scope' => $scopeKey, 'status' => $statusFilter, 'format' => 'pdf']) }}" class="inline-flex items-center rounded-lg bg-slate-900 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-white transition hover:bg-slate-800 dark:bg-cyan-500 dark:text-slate-950 dark:hover:bg-cyan-400">
                                    Descargar PDF
                                </a>
                                <a href="{{ route('admin.reports.download', ['week_scope' => $scopeKey, 'status' => $statusFilter, 'format' => 'csv']) }}" class="inline-flex items-center rounded-lg border border-slate-300 bg-white px-3 py-2 text-xs font-semibold uppercase tracking-wide text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                                    Descargar CSV
                                </a>
                                <a href="{{ route('admin.reports.download', ['week_scope' => $scopeKey, 'status' => $statusFilter, 'format' => 'xlsx']) }}" class="inline-flex items-center rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-xs font-semibold uppercase tracking-wide text-emerald-700 transition hover:bg-emerald-100 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-300 dark:hover:bg-emerald-500/20">
                                    Descargar XLSX
                                </a>
                            </div>
                        </div>

                        <div class="space-y-4">
                            @foreach ($scope['days'] as $day)
                                @php
                                    $daySlots = $slotsByScope[$scopeKey][$day['day_of_week']] ?? collect();
                                @endphp

                                <section class="rounded-2xl border border-slate-200 bg-white/95 p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                                    <div class="mb-3 flex items-center justify-between gap-2">
                                        <h3 class="text-sm font-semibold uppercase tracking-wide text-slate-700 dark:text-slate-200">
                                            {{ $day['label'] }}
                                        </h3>
                                        <span class="rounded-full bg-slate-200 px-2.5 py-1 text-xs font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-200">
                                            {{ $day['date'] }} · {{ $daySlots->count() }} cursos
                                        </span>
                                    </div>

                                    <div class="overflow-x-auto">
                                        <table class="min-w-full text-sm">
                                            <thead>
                                                <tr class="border-b border-slate-200 text-left text-xs uppercase tracking-wide text-slate-500 dark:border-slate-700 dark:text-slate-400">
                                                    <th class="px-2 py-2">Horario</th>
                                                    <th class="px-2 py-2">Curso</th>
                                                    <th class="px-2 py-2">Tema</th>
                                                    <th class="px-2 py-2">Profesor</th>
                                                    <th class="px-2 py-2">Aula</th>
                                                    <th class="px-2 py-2">Inscritos</th>
                                                    <th class="px-2 py-2">Estado</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                @forelse ($daySlots as $slot)
                                                    <tr class="border-b border-slate-100 last:border-0 dark:border-slate-800">
                                                        <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ substr($slot->starts_at, 0, 5) }} - {{ substr($slot->ends_at, 0, 5) }}</td>
                                                        <td class="px-2 py-2 text-slate-700 dark:text-slate-200">{{ $slot->courseTopic->course->name }}</td>
                                                        <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ $slot->courseTopic->title }}</td>
                                                        <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ $slot->teacher->name }}</td>
                                                        <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ $slot->classroom->name }}</td>
                                                        <td class="px-2 py-2 text-slate-600 dark:text-slate-300">{{ $slot->enrollments_count }}</td>
                                                        <td class="px-2 py-2"><x-status-badge :status="$slot->status" /></td>
                                                    </tr>
                                                @empty
                                                    <tr>
                                                        <td colspan="7" class="px-2 py-3 text-sm text-slate-500 dark:text-slate-400">Sin cursos para este dia con el filtro seleccionado.</td>
                                                    </tr>
                                                @endforelse
                                            </tbody>
                                        </table>
                                    </div>
                                </section>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </section>
        </div>
    </div>
</x-app-layout>

