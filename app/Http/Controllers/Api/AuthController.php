<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:30'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $customerRole = Role::where('name', 'customer')->firstOrFail();

        $user = User::create([
            ...$data,
            'role_id' => $customerRole->id,
            'password' => Hash::make($data['password']),
            'is_active' => true,
        ]);

        $token = $user->createToken('lifemedic-mobile')->plainTextToken;

        return response()->json([
            'data' => ['user' => $user->load('role'), 'token' => $token],
        ], 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        $user = User::with('role')->where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            throw ValidationException::withMessages([
                'email' => ['Las credenciales no son correctas.'],
            ]);
        }

        if (!$user->is_active) {
            return response()->json(['message' => 'La cuenta está inactiva.'], 403);
        }

        $token = $user->createToken('lifemedic-mobile')->plainTextToken;

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

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Sesión cerrada.']);
    }
}