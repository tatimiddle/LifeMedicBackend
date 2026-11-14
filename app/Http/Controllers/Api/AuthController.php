<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function __construct(private readonly UserManagementService $users) {}

    public function register(Request $request)
    {
        if (is_string($request->input('email'))) {
            $request->merge([
                'email' => mb_strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'address' => ['nullable', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $user = $this->users->createUser([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'password' => $data['password'],
            'role' => Role::CLIENT,
            'is_active' => true,
        ]);

        $token = $user->createToken($data['device_name'] ?? 'lifemedic-mobile')->plainTextToken;

        return response()->json([
            'data' => ['user' => $user->load('role'), 'token' => $token],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'device_name' => ['nullable', 'string', 'max:100'],
        ]);

        $email = mb_strtolower(trim((string) $data['email']));
        $user = User::with('role')->where('email', $email)->first();

        if (! $user || ! Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.'],
            ]);
        }

        $meta = $this->users->metadataForUser($user->id);
        if (! $meta['is_active']) {
            return response()->json(['message' => 'La cuenta está inactiva.'], 403);
        }

        $token = $user->createToken($data['device_name'] ?? 'lifemedic-mobile')->plainTextToken;

        return response()->json([
            'data' => ['user' => $user, 'token' => $token],
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'data' => $request->user()->load('role'),
        ]);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();

        if ($request->has('email') && is_string($request->input('email'))) {
            $request->merge([
                'email' => mb_strtolower(trim((string) $request->input('email'))),
            ]);
        }

        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'min:2', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'address' => ['sometimes', 'nullable', 'string', 'max:255'],
            'password' => ['sometimes', 'required', 'string', 'min:8', 'confirmed'],
        ]);

        $updated = $this->users->updateUser($user, $data);

        return response()->json([
            'data' => $updated->load('role'),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()?->currentAccessToken()?->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}
