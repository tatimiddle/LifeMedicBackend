@php
    $sections = \App\Support\PharmacyMockData::navigation();
@endphp

<aside class="flex h-full flex-col bg-white border-r border-zinc-200/70 select-none overflow-y-auto">
    {{-- Encabezado de marca LifeMedic visible en escritorio y móvil --}}
    <div
        data-sidebar-brand="true"
        class="flex h-14 shrink-0 items-center justify-between border-b border-zinc-200/70 px-4"
    >
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2 focus:outline-none">
            <img
                src="{{ asset('images/lifemedic-logo-dark.png') }}"
                alt="LifeMedic"
                class="h-7 w-auto object-contain"
            />
            <span class="sr-only">LifeMedic</span>
        </a>

        <button
            type="button"
            @click="sidebarOpen = false"
            aria-label="Cerrar menú"
            class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] -mr-2 rounded-[6px] text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900 lg:hidden cursor-pointer"
        >
            <x-icon name="x" class="w-4 h-4" />
        </button>
    </div>

    <div class="flex-1 space-y-5 px-3 py-4">
        @foreach ($sections as $section)
            <div>
                <p class="px-2.5 mb-1.5 text-[11.5px] font-medium text-zinc-400">
                    {{ $section['title'] }}
                </p>

                <nav class="space-y-0.5" aria-label="{{ $section['title'] }}">
                    @foreach ($section['items'] as $item)
                        @php
                            $isActive = request()->routeIs($item['route'])
                                || ($item['href'] === '/' ? request()->is('/') : request()->is(ltrim($item['href'], '/') . '*'));
                        @endphp
                        <x-sidebar-link :item="$item" :active="$isActive" />
                    @endforeach
                </nav>
            </div>
        @endforeach
    </div>

    @auth
        <div class="mt-auto border-t border-zinc-200/80 px-3 py-3">
            <div class="flex items-center justify-between gap-2 rounded-[6px] border border-zinc-200/80 bg-zinc-50/70 px-2.5 py-2">
                <div class="min-w-0">
                    <p class="truncate text-xs font-semibold text-zinc-900">{{ auth()->user()->name }}</p>
                    <p class="truncate text-[11px] text-zinc-500">Administrador</p>
                </div>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button
                        type="submit"
                        title="Cerrar sesión"
                        aria-label="Cerrar sesión"
                        class="inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-[6px] border border-zinc-200 bg-white text-zinc-600 transition-colors hover:bg-[#095d52] hover:border-[#095d52] hover:text-white cursor-pointer"
                    >
                        <x-icon name="logout" class="w-4 h-4" />
                    </button>
                </form>
            </div>
        </div>
    @endauth
</aside>
