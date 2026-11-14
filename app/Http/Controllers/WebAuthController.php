<?php

namespace App\Http\Controllers;

use App\Services\UserManagementService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class WebAuthController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function showLogin(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user) {
            $meta = $this->users->metadataForUser($user->id);
            if ($meta['is_active'] && $meta['role'] === 'administrador') {
                return redirect()->route('dashboard');
            }
        }

        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ], [
            'email.required' => 'Ingresa tu correo electrónico.',
            'email.email' => 'Ingresa un correo electrónico válido.',
            'password.required' => 'Ingresa tu contraseña.',
        ]);

        $remember = $request->boolean('remember');

        if (! Auth::attempt([
            'email' => mb_strtolower(trim($credentials['email'])),
            'password' => $credentials['password'],
        ], $remember)) {
            return back()
                ->withInput($request->only('email', 'remember'))
                ->withErrors([
                    'email' => 'Las credenciales ingresadas no coinciden con nuestros registros.',
                ]);
        }

        $user = $request->user();
        $meta = $user ? $this->users->metadataForUser($user->id) : null;

        if (! $user || ! $meta || ! $meta['is_active']) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Tu cuenta se encuentra desactivada. Contacta al administrador.',
                ]);
        }

        if ($meta['role'] !== 'administrador') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->withErrors([
                    'email' => 'Acceso restringido. Solo usuarios con rol Administrador pueden acceder al panel.',
                ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()
            ->route('login')
            ->with('status_message', 'Has cerrado sesión correctamente.');
    }
}
