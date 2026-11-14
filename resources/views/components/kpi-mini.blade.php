@props(['label', 'value', 'trend' => null, 'positive' => true, 'icon' => 'medicine', 'plain' => false])

<div {{ $attributes->merge([
    'class' => $plain
        ? 'flex items-center justify-between gap-3 px-1 py-0.5'
        : 'flex items-center justify-between gap-3 rounded-[6px] bg-zinc-100/75 px-3.5 py-2.5',
]) }}>
    <div class="flex items-center gap-3 min-w-0">
        <span @class([
            'flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] text-zinc-600',
            'bg-zinc-100' => $plain,
            'bg-white shadow-[0_1px_2px_rgba(9,9,11,0.04)]' => ! $plain,
        ])>
            <x-icon :name="$icon" class="w-4 h-4" />
        </span>
        <div class="min-w-0">
            <p class="truncate text-[11.5px] font-medium text-zinc-500">{{ $label }}</p>
            <p class="text-[17px] font-semibold tracking-tight text-zinc-950 tabular-nums leading-snug">{{ $value }}</p>
        </div>
    </div>

    @if ($trend)
        <span @class([
            'inline-flex shrink-0 items-center gap-0.5 text-[11.5px] font-medium tabular-nums',
            'text-[#15803d]' => $positive,
            'text-[#c2410c]' => ! $positive,
        ])>
            @if ($positive)
                <x-icon name="trend-up" class="w-3.5 h-3.5" />
            @else
                <x-icon name="trend-down" class="w-3.5 h-3.5" />
            @endif
            <span>{{ $trend }}</span>
        </span>
    @endif
</div>
