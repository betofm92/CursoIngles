@props(['active'])

@php
$classes = ($active ?? false)
            ? 'block w-full ps-3 pe-4 py-2 border-l-4 border-cyan-500 text-start text-base font-semibold text-cyan-800 bg-cyan-50 focus:outline-none transition dark:border-cyan-400 dark:text-cyan-200 dark:bg-cyan-500/10'
            : 'block w-full ps-3 pe-4 py-2 border-l-4 border-transparent text-start text-base font-medium text-slate-600 hover:text-slate-800 hover:bg-slate-50 hover:border-slate-300 focus:outline-none transition dark:text-slate-400 dark:hover:text-slate-200 dark:hover:bg-slate-800/50 dark:hover:border-slate-600';
@endphp

<a {{ $attributes->merge(['class' => $classes]) }}>
    {{ $slot }}
</a>
