@props(['variant' => 'default'])

<span {{ $attributes->class(['badge', 'gold' => $variant === 'gold']) }}>
    {{ $slot }}
</span>
