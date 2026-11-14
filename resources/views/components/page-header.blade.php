@props(['title', 'subtitle' => null, 'actionLabel' => null, 'actionClick' => null])

<div data-page-header="true" class="mb-5 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <div>
        <h1 class="text-xl font-semibold tracking-tight text-zinc-950 sm:text-2xl">{{ $title }}</h1>
        @if ($subtitle)
            <p class="mt-0.5 text-sm text-zinc-500">{{ $subtitle }}</p>
        @endif
    </div>

    <div class="flex flex-wrap items-center gap-2.5">
        {{ $slot }}
        @if ($actionLabel)
            <button
                type="button"
                @if ($actionClick) @click="{{ $actionClick }}" @endif
                class="inline-flex items-center gap-1.5 rounded-[6px] bg-[#095d52] px-3.5 py-2 text-xs font-medium text-white transition-colors hover:bg-[#064940] active:scale-[0.98] cursor-pointer"
            >
                <x-icon name="plus" class="w-4 h-4" />
                <span>{{ $actionLabel }}</span>
            </button>
        @endif
    </div>
</div>
