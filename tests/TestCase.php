<?php

namespace Tests;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function actingAsAdmin(string $email = 'admin@lifemedic.com'): User
    {
        $service = app(UserManagementService::class);
        $existing = User::query()->where('email', $email)->first();

        $user = $existing ?? $service->createUser([
            'name' => 'Administrador LifeMedic',
            'email' => $email,
            'password' => 'AdminLifeMedic123!',
            'role' => 'administrador',
            'is_active' => true,
        ]);

        if ($existing) {
            $service->assignRole($user, 'administrador');
            $service->updateUser($user, ['is_active' => true]);
        }

        $this->actingAs($user);

        return $user;
    }
}
