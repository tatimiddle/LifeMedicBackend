@props(['title' => 'Panel', 'hideDesktopHeader' => false])

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — {{ config('app.name', 'LifeMedic') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-[#fafafa] text-zinc-950 antialiased overflow-x-hidden" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen flex flex-col lg:flex-row">
        {{-- Barra compacta móvil/tablet (< lg) --}}
        <header class="sticky top-0 z-30 flex h-14 items-center justify-between border-b border-zinc-200 bg-white px-4 lg:hidden">
            <div class="flex items-center gap-2.5">
                <button
                    type="button"
                    @click="sidebarOpen = true"
                    aria-label="Abrir menú lateral"
                    class="inline-flex items-center justify-center min-h-[44px] min-w-[44px] -ml-2 rounded-[6px] text-zinc-700 transition-colors hover:bg-zinc-100 cursor-pointer"
                >
                    <x-icon name="menu" class="w-5 h-5" />
                </button>
                <div class="flex items-center gap-2">
                    <span class="text-sm font-semibold text-zinc-950 leading-tight">{{ $title }}</span>
                    <span class="text-xs text-zinc-400">· LifeMedic</span>
                </div>
            </div>
        </header>

        {{-- Overlay móvil --}}
        <div
            x-cloak
            x-show="sidebarOpen"
            x-transition.opacity
            @click="sidebarOpen = false"
            class="fixed inset-0 z-40 bg-zinc-950/35 lg:hidden"
        ></div>

        {{-- Drawer móvil --}}
        <div
            x-cloak
            x-show="sidebarOpen"
            x-on:keydown.escape.window="sidebarOpen = false"
            x-transition:enter="transition-transform ease-out duration-200"
            x-transition:enter-start="-translate-x-full"
            x-transition:enter-end="translate-x-0"
            x-transition:leave="transition-transform ease-in duration-150"
            x-transition:leave-start="translate-x-0"
            x-transition:leave-end="-translate-x-full"
            class="fixed inset-y-0 left-0 z-50 w-[264px] lg:hidden"
        >
            <x-sidebar />
        </div>

        {{-- Sidebar fijo desktop (lg+) --}}
        <div class="hidden lg:block lg:w-[240px] lg:shrink-0 lg:sticky lg:top-0 lg:h-screen">
            <x-sidebar />
        </div>

        {{-- Contenido Principal --}}
        <main class="flex-1 min-w-0 p-4 sm:p-5 lg:p-6">
            @if (! $hideDesktopHeader && isset($header))
                <div data-desktop-header="true" class="mb-5">
                    {{ $header }}
                </div>
            @endif

            {{ $slot }}
        </main>
    </div>

    <x-toast />
</body>
</html>
