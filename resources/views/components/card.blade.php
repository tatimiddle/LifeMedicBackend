@props(['padding' => 'p-5'])

<section {{ $attributes->merge(['class' => "lm-card {$padding}"]) }}>
    {{ $slot }}
</section>
