@props(['item', 'active' => false])

@php
    $url = \Illuminate\Support\Facades\Route::has($item['route'])
        ? route($item['route'])
        : url($item['href']);
@endphp

<a
    href="{{ $url }}"
    @class([
        'group flex h-9 items-center gap-2.5 px-2.5 rounded-[6px] text-[13.5px] font-medium transition-colors duration-150',
        'bg-zinc-900 text-white' => $active,
        'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-950' => ! $active,
    ])
>
    <x-icon
        :name="$item['icon']"
        @class([
            'w-4 h-4 shrink-0 transition-colors duration-150',
            'text-white' => $active,
            'text-zinc-400 group-hover:text-zinc-800' => ! $active,
        ])
    />
    <span class="truncate">{{ $item['label'] }}</span>
</a>
