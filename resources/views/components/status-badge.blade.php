@props(['status'])

@php
    $styles = match ($status) {
        'confirmed' => 'bg-emerald-100 text-emerald-800 border-emerald-200 dark:bg-emerald-500/10 dark:text-emerald-200 dark:border-emerald-500/40',
        'closed' => 'bg-slate-200 text-slate-700 border-slate-300 dark:bg-slate-700/40 dark:text-slate-200 dark:border-slate-600',
        default => 'bg-amber-100 text-amber-800 border-amber-200 dark:bg-amber-500/10 dark:text-amber-200 dark:border-amber-500/40',
    };

    $label = match ($status) {
        'confirmed' => 'Confirmado',
        'closed' => 'Cerrado',
        default => 'Borrador',
    };
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center rounded-full border px-2.5 py-1 text-xs font-semibold uppercase tracking-wide {$styles}"]) }}>
    {{ $label }}
</span>
