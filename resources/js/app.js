import Alpine from 'alpinejs';

window.lifemedic = {
    money(value) {
        return '$' + Number(value || 0).toLocaleString('en-US', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2,
        });
    },
    statusClass() {
        return 'bg-white text-zinc-800 border-zinc-200';
    },
    statusDot(status) {
        const normalized = String(status || '').trim().toLowerCase();
        if (['pagado', 'pagada', 'completado', 'completada', 'entregado', 'vigente', 'activo', 'respondida', 'disponible'].includes(normalized)) {
            return 'bg-[#0f766e]';
        }
        if (['parcial', 'en preparación', 'en_preparacion', 'en preparacion', 'confirmado', 'en revisión'].includes(normalized)) {
            return 'bg-[#095d52]';
        }
        if (['pendiente', 'por vencer', 'stock bajo'].includes(normalized)) {
            return 'bg-[#d97706]';
        }
        if (['cancelado', 'vencida', 'agotado', 'reembolsada', 'inactivo'].includes(normalized)) {
            return 'bg-[#dc2626]';
        }
        return 'bg-zinc-400';
    },
};

window.Alpine = Alpine;
Alpine.start();
