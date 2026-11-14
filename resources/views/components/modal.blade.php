@props(['name', 'title', 'maxWidth' => 'max-w-lg'])

<div
    x-cloak
    x-show="{{ $name }}"
    x-on:keydown.escape.window="{{ $name }} = false"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
>
    <div
        x-show="{{ $name }}"
        x-transition.opacity
        @click="{{ $name }} = false"
        class="fixed inset-0 bg-zinc-950/40"
    ></div>

    <div
        x-show="{{ $name }}"
        role="dialog"
        aria-modal="true"
        aria-label="{{ $title }}"
        x-transition:enter="transition-[opacity,transform] ease-out duration-150"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition-[opacity,transform] ease-in duration-100"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="relative z-10 w-full {{ $maxWidth }} rounded-[10px] border border-zinc-200 bg-white p-5 shadow-[0_12px_32px_-8px_rgba(9,9,11,0.16)]"
    >
        <div class="mb-4 flex items-center justify-between gap-4 border-b border-zinc-200 pb-3">
            <h3 class="text-[15px] font-semibold text-zinc-950">{{ $title }}</h3>
            <button
                type="button"
                @click="{{ $name }} = false"
                aria-label="Cerrar ventana"
                class="inline-flex h-8 w-8 items-center justify-center rounded-[6px] text-zinc-400 transition-colors hover:bg-zinc-100 hover:text-zinc-800 cursor-pointer"
            >
                <x-icon name="x" class="w-4 h-4" />
            </button>
        </div>

        {{ $slot }}
    </div>
</div>
