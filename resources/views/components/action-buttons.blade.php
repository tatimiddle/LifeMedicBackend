@props([
    'viewClick' => null,
    'editClick' => null,
    'deleteClick' => null,
])

<div class="inline-flex items-center gap-1">
    <button
        type="button"
        @if ($viewClick) @click="{{ $viewClick }}" @endif
        aria-label="Ver"
        title="Ver detalle"
        class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-[#095d52] hover:text-white active:scale-[0.96] cursor-pointer"
    >
        <x-icon name="eye" class="w-3.5 h-3.5" />
    </button>

    <button
        type="button"
        @if ($editClick) @click="{{ $editClick }}" @endif
        aria-label="Editar"
        title="Editar registro"
        class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-zinc-900 hover:text-white active:scale-[0.96] cursor-pointer"
    >
        <x-icon name="edit" class="w-3.5 h-3.5" />
    </button>

    <button
        type="button"
        @if ($deleteClick) @click="{{ $deleteClick }}" @endif
        aria-label="Eliminar"
        title="Eliminar registro"
        class="inline-flex h-7 w-7 items-center justify-center rounded-[6px] bg-zinc-100/80 text-zinc-600 transition-colors hover:bg-red-600 hover:text-white active:scale-[0.96] cursor-pointer"
    >
        <x-icon name="trash" class="w-3.5 h-3.5" />
    </button>
</div>
