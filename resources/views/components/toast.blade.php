<div
    x-data="{
        toasts: [],
        add(detail) {
            const id = Date.now() + Math.random();
            this.toasts.push({
                id,
                message: detail?.message ?? 'Acción realizada correctamente.',
                type: detail?.type ?? 'success',
            });
            setTimeout(() => {
                this.toasts = this.toasts.filter(t => t.id !== id);
            }, 3400);
        }
    }"
    x-on:notify.window="add($event.detail)"
    class="fixed right-4 bottom-4 z-50 flex flex-col gap-2 max-w-sm w-full pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition
            class="pointer-events-auto flex items-center justify-between gap-3 rounded-[6px] border border-zinc-200 bg-white px-4 py-3 text-[13px] font-medium text-zinc-900 shadow-[0_8px_20px_-6px_rgba(9,9,11,0.12)]"
        >
            <div class="flex items-center gap-2.5">
                <span
                    class="h-2 w-2 rounded-full shrink-0"
                    :class="{
                        'bg-[#0f766e]': toast.type === 'success',
                        'bg-[#095d52]': toast.type === 'info',
                        'bg-[#d97706]': toast.type === 'warning',
                    }"
                ></span>
                <span x-text="toast.message"></span>
            </div>
            <button
                type="button"
                @click="toasts = toasts.filter(t => t.id !== toast.id)"
                class="text-zinc-400 transition-colors hover:text-zinc-800 cursor-pointer"
                aria-label="Cerrar notificación"
            >
                <x-icon name="x" class="w-4 h-4" />
            </button>
        </div>
    </template>
</div>
