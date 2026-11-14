<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <meta name="csrf-token" content="{{ csrf_token() }}" />
    <title>Iniciar Sesión — LifeMedic</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Geist:wght@400;500;600;700&display=swap" rel="stylesheet" />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body
    class="min-h-screen bg-white font-sans text-zinc-950 antialiased"
    x-data="{ showPassword: false, forgotModal: false, termsModal: false }"
>
    <div class="min-h-screen grid grid-cols-1 lg:grid-cols-12 bg-white">
        {{-- Columna izquierda: formulario minimalista alineado --}}
        <div class="lg:col-span-6 flex flex-col items-center justify-between bg-white px-6 py-10 sm:px-12 lg:px-16">
            <div class="w-full max-w-[360px] flex-1 flex flex-col justify-between">
                <div class="pt-3">
                    <a href="{{ route('login') }}" class="inline-flex items-center focus:outline-none">
                        <img
                            src="{{ asset('images/lifemedic-logo-dark.png') }}"
                            alt="LifeMedic"
                            class="h-7 w-auto object-contain"
                        />
                        <span class="sr-only">LifeMedic</span>
                    </a>
                </div>

                <div class="my-auto py-8">
                    <div class="mb-6">
                        <h1 class="text-[24px] font-semibold tracking-tight text-zinc-950">Inicia sesión en tu cuenta</h1>
                        <p class="mt-1 text-[13.5px] text-zinc-500">
                            Por favor ingresa tus credenciales
                        </p>
                    </div>

                    @if (session('status_message'))
                        <div class="mb-5 flex items-center gap-2.5 rounded-[6px] border border-zinc-200 bg-white px-3.5 py-2.5 text-xs font-medium text-zinc-800">
                            <span class="h-1.5 w-1.5 shrink-0 rounded-full bg-[#095d52]" aria-hidden="true"></span>
                            <span>{{ session('status_message') }}</span>
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="mb-5 rounded-[6px] border border-red-200 bg-white px-3.5 py-2.5 text-xs text-red-700" role="alert">
                            <p class="font-semibold text-[#dc2626]">No se pudo iniciar sesión</p>
                            <ul class="mt-1 space-y-0.5 list-disc pl-4">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form method="POST" action="{{ route('login.store') }}" class="space-y-4">
                        @csrf

                        <div>
                            <label for="email" class="block text-[13px] font-medium text-zinc-800 mb-1.5">
                                Correo electrónico
                            </label>
                            <div class="relative">
                                <span class="pointer-events-none absolute inset-y-0 left-0 flex items-center pl-3 text-zinc-400">
                                    <x-icon name="mail" class="w-4 h-4" />
                                </span>
                                <input
                                    id="email"
                                    name="email"
                                    type="email"
                                    value="{{ old('email') }}"
                                    required
                                    autocomplete="email"
                                    autofocus
                                    placeholder="Ingresa tu correo electrónico"
                                    class="w-full rounded-[6px] border border-zinc-200 bg-white py-2.5 pr-3 pl-9 text-sm text-zinc-950 placeholder:text-zinc-400 transition-colors focus:border-[#095d52] focus:outline-none focus:ring-2 focus:ring-[#095d52]/15"
                                />
                            </div>
                        </div>

                        <div>
                            <label for="password" class="block text-[13px] font-medium text-zinc-800 mb-1.5">
                                Contraseña
                            </label>
                            <div class="relative">
                                <input
                                    id="password"
                                    name="password"
                                    :type="showPassword ? 'text' : 'password'"
                                    required
                                    autocomplete="current-password"
                                    placeholder="••••••••"
                                    class="w-full rounded-[6px] border border-zinc-200 bg-white py-2.5 pr-10 pl-3.5 text-sm text-zinc-950 placeholder:text-zinc-400 transition-colors focus:border-[#095d52] focus:outline-none focus:ring-2 focus:ring-[#095d52]/15"
                                />
                                <button
                                    type="button"
                                    @click="showPassword = !showPassword"
                                    class="absolute inset-y-0 right-0 flex items-center pr-3 text-zinc-400 hover:text-zinc-700 cursor-pointer"
                                    aria-label="Mostrar u ocultar contraseña"
                                >
                                    <span x-show="!showPassword">
                                        <x-icon name="eye" class="w-4 h-4" />
                                    </span>
                                    <span x-show="showPassword" style="display: none;">
                                        <x-icon name="eye-off" class="w-4 h-4" />
                                    </span>
                                </button>
                            </div>
                        </div>

                        <div class="flex items-center justify-between gap-2 pt-0.5">
                            <label class="inline-flex items-center gap-2 text-xs text-zinc-600 cursor-pointer select-none">
                                <input
                                    type="checkbox"
                                    name="remember"
                                    value="1"
                                    @checked(old('remember'))
                                    class="h-3.5 w-3.5 rounded border-zinc-300 text-[#095d52] focus:ring-[#095d52]"
                                />
                                <span>Recordar sesión por 30 días</span>
                            </label>

                            <button
                                type="button"
                                @click="forgotModal = true"
                                class="text-xs font-medium text-[#095d52] hover:text-[#064940] hover:underline cursor-pointer"
                            >
                                ¿Olvidaste tu contraseña?
                            </button>
                        </div>

                        <button
                            type="submit"
                            class="mt-1 w-full rounded-[6px] bg-[#095d52] px-4 py-2.5 text-[13.5px] font-medium text-white shadow-xs transition-colors hover:bg-[#064940] active:scale-[0.99] cursor-pointer"
                        >
                            Iniciar sesión
                        </button>
                    </form>
                </div>

                <div class="pb-1 text-left text-xs text-zinc-400">
                    Al iniciar sesión aceptas nuestros
                    <button
                        type="button"
                        @click="termsModal = true"
                        class="font-medium text-zinc-600 underline hover:text-[#095d52] cursor-pointer"
                    >
                        Términos de Uso
                    </button>.
                </div>
            </div>
        </div>

        {{-- Columna derecha: tarjeta inset Verde Aqua AAA + iPad Pro con dashboard real --}}
        <div class="hidden lg:flex lg:col-span-6 p-3.5 lg:p-4 bg-white">
            <div
                class="relative w-full h-full min-h-[660px] rounded-[14px] overflow-hidden shadow-sm"
                style="background: linear-gradient(150deg, #0C7567 0%, #095D52 44%, #043C34 100%);"
            >
                {{-- Ondas arquitectónicas sutiles en fondo Verde Aqua --}}
                <svg
                    class="pointer-events-none select-none absolute inset-0 h-full w-full"
                    viewBox="0 0 800 900"
                    fill="none"
                    preserveAspectRatio="none"
                    aria-hidden="true"
                >
                    <path
                        d="M-60 440C140 310 380 260 640 30C710 -30 780 -60 850 -80L850 560C610 560 320 670 -60 840Z"
                        fill="rgba(255,255,255,0.045)"
                    />
                    <path
                        d="M120 900C340 620 570 470 860 360L860 900Z"
                        fill="rgba(0,0,0,0.09)"
                    />
                    <circle cx="180" cy="140" r="280" fill="rgba(255,255,255,0.035)" />
                </svg>

                <div class="relative z-10 px-10 pt-10 xl:px-12 xl:pt-12 max-w-[340px]">
                    <div class="inline-flex items-center">
                        <img
                            src="{{ asset('images/lifemedic-logo-light.png') }}"
                            alt="LifeMedic"
                            class="h-7 w-auto object-contain"
                        />
                    </div>

                    <h2 class="mt-5 text-[28px] xl:text-[31px] leading-[1.18] font-medium tracking-[-0.02em] text-white">
                        Control clínico<br />en tiempo real
                    </h2>
                </div>

                <img
                    src="{{ asset('images/login-tablet-mockup.png') }}"
                    alt="Mockup del panel administrativo LifeMedic en tablet Apple"
                    class="pointer-events-none select-none absolute left-[16%] top-[21%] w-[122%] max-w-none h-auto z-10"
                />
            </div>
        </div>
    </div>

    {{-- Modal recuperación --}}
    <div
        x-show="forgotModal"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Recuperación de contraseña"
    >
        <div class="w-full max-w-md rounded-[8px] border border-zinc-200 bg-white p-6 shadow-xl" @click.away="forgotModal = false">
            <h3 class="text-base font-semibold text-zinc-950">Recuperar acceso administrativo</h3>
            <p class="mt-2 text-xs leading-relaxed text-zinc-600">
                Por seguridad farmacéutica y trazabilidad de auditoría, el restablecimiento de contraseñas administrativas lo realiza un administrador principal desde el módulo Usuarios y Roles o mediante soporte técnico interno.
            </p>
            <div class="mt-5 flex justify-end">
                <button
                    type="button"
                    @click="forgotModal = false"
                    class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer"
                >
                    Entendido
                </button>
            </div>
        </div>
    </div>

    {{-- Modal términos --}}
    <div
        x-show="termsModal"
        x-transition.opacity
        style="display: none;"
        class="fixed inset-0 z-50 flex items-center justify-center bg-zinc-950/50 p-4"
        role="dialog"
        aria-modal="true"
        aria-label="Términos de Uso"
    >
        <div class="w-full max-w-md rounded-[8px] border border-zinc-200 bg-white p-6 shadow-xl" @click.away="termsModal = false">
            <h3 class="text-base font-semibold text-zinc-950">Términos de Uso — LifeMedic Admin</h3>
            <p class="mt-2 text-xs leading-relaxed text-zinc-600">
                El acceso al panel administrativo de LifeMedic está reservado para personal autorizado con rol de Administrador. Toda creación, ajuste de precios/stock, baja lógica de medicamentos y actualización de pedidos queda vinculada a la sesión activa.
            </p>
            <div class="mt-5 flex justify-end">
                <button
                    type="button"
                    @click="termsModal = false"
                    class="rounded-[6px] bg-[#095d52] px-4 py-2 text-xs font-medium text-white hover:bg-[#064940] cursor-pointer"
                >
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</body>
</html>
