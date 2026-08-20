@props(['value'])

<label {{ $attributes->merge(['class' => 'block text-sm font-medium text-secondary dark:text-light']) }}>
    {{ $value ?? $slot }}
</label>
