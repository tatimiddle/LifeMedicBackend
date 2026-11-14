<?php

namespace App\Http\Middleware;

use App\Services\UserManagementService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsAdmin
{
    public function __construct(private readonly UserManagementService $users) {}

    /**
     * Restringe el acceso al panel administrativo exclusivamente a cuentas activas con rol de administrador.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        $meta = $this->users->metadataForUser($user->id);

        if (! $meta['is_active']) {
            Auth::guard('web')->logout();
            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()
                ->route('login')
                ->withErrors(['email' => 'Tu cuenta se encuentra desactivada. Contacta al administrador principal.']);
        }

        if ($meta['role'] !== 'administrador') {
            abort(Response::HTTP_FORBIDDEN, 'Acceso denegado. Este panel es exclusivo para usuarios con rol Administrador.');
        }

        return $next($request);
    }
}
