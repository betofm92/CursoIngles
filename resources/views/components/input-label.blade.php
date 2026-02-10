@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400']) }}>
    {{ $value ?? $slot }}
</label>
