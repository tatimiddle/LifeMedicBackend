<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\LoginRequest;
use App\Http\Requests\Api\RegisterRequest;
use App\Http\Requests\Api\UpdateProfileRequest;
use App\Http\Resources\Api\UserResource;
use App\Models\User;
use App\Services\UserManagementService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $user = $this->users->createUser([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'address' => $validated['address'] ?? null,
            'password' => $validated['password'],
            'role' => 'cliente',
            'is_active' => true,
        ]);

        $token = $user->createToken($validated['device_name'] ?? 'lifemedic-mobile')->plainTextToken;

        return ApiResponse::created([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Usuario registrado correctamente.');
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $email = mb_strtolower(trim((string) $validated['email']));

        $user = User::query()->where('email', $email)->first();

        if (! $user || ! Hash::check($validated['password'], $user->password)) {
            return ApiResponse::error(
                'Credenciales inválidas. Verifica tu correo y contraseña.',
                Response::HTTP_UNAUTHORIZED
            );
        }

        $meta = $this->users->metadataForUser($user->id);
        if (! $meta['is_active']) {
            return ApiResponse::error(
                'Tu cuenta se encuentra desactivada. Contacta al administrador.',
                Response::HTTP_FORBIDDEN
            );
        }

        $token = $user->createToken($validated['device_name'] ?? 'lifemedic-mobile')->plainTextToken;

        return ApiResponse::success([
            'user' => (new UserResource($user))->resolve(),
            'token' => $token,
            'token_type' => 'Bearer',
        ], 'Inicio de sesión exitoso.');
    }

    public function profile(Request $request): JsonResponse
    {
        return ApiResponse::success([
            'user' => (new UserResource($request->user()))->resolve(),
        ], 'Perfil de usuario obtenido correctamente.');
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        $updated = $this->users->updateUser($request->user(), $request->validated());

        return ApiResponse::success([
            'user' => (new UserResource($updated))->resolve(),
        ], 'Perfil actualizado correctamente.');
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()?->currentAccessToken()?->delete();

        return ApiResponse::success(null, 'Sesión cerrada correctamente.');
    }
}
