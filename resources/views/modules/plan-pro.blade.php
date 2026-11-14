<x-layouts.app title="Plan Pro">
    <div
        class="space-y-6"
        x-data="{
            billing: 'Mensual',
            confirmModal: false,
            selectedPlan: null,
            openConfirm(planName, price) {
                this.selectedPlan = { name: planName, price, billing: this.billing };
                this.confirmModal = true;
            },
            confirmUpgrade() {
                this.confirmModal = false;
                this.$dispatch('notify', { message: `Solicitud simulada para ${this.selectedPlan.name} (${this.selectedPlan.billing}) enviada.`, type: 'success' });
            }
        }"
    >
        <x-page-header
            title="Plan Pro"
            subtitle="Escala la operación de tu farmacia con analíticas detalladas y alertas en tiempo real"
        >
            <div class="inline-flex items-center rounded-[6px] border border-zinc-200 bg-white p-1">
                <button
                    type="button"
                    @click="billing = 'Mensual'"
                    :class="billing === 'Mensual' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:text-zinc-900'"
                    class="rounded-[4px] px-3 py-1.5 text-xs font-medium transition-colors cursor-pointer"
                >
                    Mensual
                </button>
                <button
                    type="button"
                    @click="billing = 'Anual'"
                    :class="billing === 'Anual' ? 'bg-zinc-900 text-white' : 'text-zinc-600 hover:text-zinc-900'"
                    class="inline-flex items-center gap-1.5 rounded-[4px] px-3 py-1.5 text-xs font-medium transition-colors cursor-pointer"
                >
                    <span>Anual</span>
                    <span class="rounded-[4px] border border-zinc-200 bg-zinc-50 px-1.5 py-0.5 text-[10px] font-semibold text-[#15803d] tabular-nums">-17%</span>
                </button>
            </div>
        </x-page-header>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-5 max-w-5xl">
            @foreach ($plans as $plan)
                @if ($plan['featured'])
                    <div class="relative overflow-hidden rounded-[8px] border border-zinc-900 bg-zinc-950 p-6 text-white flex flex-col justify-between">
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <span class="rounded-[5px] border border-white/15 bg-white/10 px-2.5 py-0.5 text-[11px] font-medium text-zinc-100">Recomendado</span>
                                <span class="text-xs font-medium text-zinc-400" x-text="'Facturación ' + billing"></span>
                            </div>

                            <h2 class="text-xl font-semibold tracking-tight">{{ $plan['name'] }}</h2>
                            <p class="mt-1.5 text-xs leading-relaxed text-zinc-400">{{ $plan['description'] }}</p>

                            <div class="my-5 flex items-baseline gap-1.5">
                                <span
                                    class="text-4xl font-semibold tracking-tight tabular-nums"
                                    x-text="billing === 'Mensual' ? '${{ $plan['monthly'] }}' : '${{ $plan['annual'] }}'"
                                >${{ $plan['monthly'] }}</span>
                                <span class="text-xs text-zinc-400">/ mes</span>
                            </div>

                            <ul class="space-y-2.5 text-xs text-zinc-200">
                                @foreach ($plan['features'] as $feat)
                                    <li class="flex items-center gap-2.5">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0 text-[#60a5fa]" aria-hidden="true">
                                            <path d="M3.5 8.5l3 3 6-6.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span>{{ $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <button
                            type="button"
                            @click="openConfirm('{{ $plan['name'] }}', billing === 'Mensual' ? '{{ $plan['monthly'] }}' : '{{ $plan['annual'] }}')"
                            class="mt-6 min-h-11 w-full rounded-[6px] bg-[#095d52] px-4 py-2.5 text-xs font-medium text-white transition-colors hover:bg-[#064940] cursor-pointer active:scale-[0.98]"
                        >
                            Actualizar a Plan Pro
                        </button>
                    </div>
                @else
                    <x-card padding="p-6" class="flex flex-col justify-between">
                        <div>
                            <div class="mb-4 flex items-center justify-between">
                                <span class="rounded-[5px] border border-zinc-200 bg-zinc-50 px-2.5 py-0.5 text-[11px] font-medium text-zinc-700">Plan Actual</span>
                                <span class="text-xs font-medium text-zinc-400" x-text="'Facturación ' + billing"></span>
                            </div>

                            <h2 class="text-xl font-semibold tracking-tight text-zinc-900">{{ $plan['name'] }}</h2>
                            <p class="mt-1.5 text-xs leading-relaxed text-zinc-500">{{ $plan['description'] }}</p>

                            <div class="my-5 flex items-baseline gap-1.5">
                                <span
                                    class="text-4xl font-semibold tracking-tight text-zinc-900 tabular-nums"
                                    x-text="billing === 'Mensual' ? '${{ $plan['monthly'] }}' : '${{ $plan['annual'] }}'"
                                >${{ $plan['monthly'] }}</span>
                                <span class="text-xs text-zinc-500">/ mes</span>
                            </div>

                            <ul class="space-y-2.5 text-xs text-zinc-700">
                                @foreach ($plan['features'] as $feat)
                                    <li class="flex items-center gap-2.5">
                                        <svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.8" class="h-4 w-4 shrink-0 text-[#0f766e]" aria-hidden="true">
                                            <path d="M3.5 8.5l3 3 6-6.5" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <span>{{ $feat }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        <button
                            type="button"
                            @click="openConfirm('{{ $plan['name'] }}', billing === 'Mensual' ? '{{ $plan['monthly'] }}' : '{{ $plan['annual'] }}')"
                            class="mt-6 min-h-11 w-full rounded-[6px] border border-zinc-200 bg-white px-4 py-2.5 text-xs font-medium text-zinc-800 transition-colors hover:bg-zinc-50 cursor-pointer active:scale-[0.98]"
                        >
                            Mantener Plan Básico
                        </button>
                    </x-card>
                @endif
            @endforeach
        </div>

        <x-modal name="confirmModal" title="Confirmar Suscripción Simulada" maxWidth="max-w-md">
            <template x-if="selectedPlan">
                <div class="space-y-4 text-sm">
                    <p class="text-zinc-600">
                        ¿Deseas solicitar la activación de <strong class="font-semibold text-zinc-900" x-text="selectedPlan.name"></strong>
                        en modalidad <strong class="font-semibold text-zinc-900" x-text="selectedPlan.billing"></strong>
                        por <strong class="font-semibold text-[#095d52]" x-text="'$' + selectedPlan.price + '/mes'"></strong>?
                    </p>
                    <div class="flex justify-end gap-2">
                        <button type="button" @click="confirmModal = false" class="rounded-[6px] border border-zinc-200 bg-white px-4 py-2 text-xs font-medium text-zinc-700 hover:bg-zinc-50 cursor-pointer">Cancelar</button>
                        <button type="button" @click="confirmUpgrade()" class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer">Enviar Solicitud</button>
                    </div>
                </div>
            </template>
        </x-modal>
    </div>
</x-layouts.app>
