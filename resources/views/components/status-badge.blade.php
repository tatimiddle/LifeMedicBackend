@props(['status'])

@php
    $normalized = mb_strtolower((string) $status);
    $dotColor = match ($normalized) {
        'pagado', 'pagada', 'completado', 'completada', 'entregado', 'vigente', 'activo', 'respondida', 'disponible' => 'bg-[#0f766e]',
        'parcial', 'confirmado', 'en preparación', 'en preparacion', 'en revisión' => 'bg-[#095d52]',
        'pendiente', 'por vencer', 'stock bajo' => 'bg-[#d97706]',
        'cancelado', 'vencida', 'agotado', 'reembolsada', 'inactivo' => 'bg-[#dc2626]',
        default => 'bg-zinc-400',
    };
@endphp

<span {{ $attributes->merge(['class' => 'inline-flex items-center gap-1.5 rounded-[5px] border border-zinc-200 bg-white px-2 py-0.5 text-[11.5px] font-medium text-zinc-800']) }}>
    <span class="h-1.5 w-1.5 shrink-0 rounded-full {{ $dotColor }}" aria-hidden="true"></span>
    <span>{{ $status }}</span>
</span>
