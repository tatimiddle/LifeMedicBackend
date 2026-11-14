<?php

namespace App\Http\Middleware;

use App\Services\UserManagementService;
use App\Support\ApiResponse;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    public function __construct(private readonly UserManagementService $users) {}

    /**
     * Verifica que el usuario autenticado por token mantenga su cuenta activa en LifeMedic.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $meta = $this->users->metadataForUser($user->id);

            if (! $meta['is_active']) {
                return ApiResponse::error(
                    'Tu cuenta ha sido desactivada por un administrador.',
                    Response::HTTP_FORBIDDEN
                );
            }
        }

        return $next($request);
    }
}
