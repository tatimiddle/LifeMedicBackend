<?php

namespace Tests\Feature\Api;

use App\Models\User;
use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_mobile_user_can_register_and_receive_sanctum_token(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'María Fernanda López',
            'email' => 'maria.lopez@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
            'device_name' => 'Android App',
        ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 201)
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonPath('data.user.email', 'maria.lopez@lifemedic.com')
            ->assertJsonPath('data.user.role', 'cliente')
            ->assertJsonPath('data.user.is_active', true);

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('users', ['email' => 'maria.lopez@lifemedic.com']);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_returns_standardized_422_validation_error_on_duplicate_email(): void
    {
        User::factory()->create(['email' => 'duplicado@lifemedic.com']);

        $response = $this->postJson('/api/auth/register', [
            'name' => 'Usuario Repetido',
            'email' => 'duplicado@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ]);

        $response->assertStatus(422)
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 422)
            ->assertJsonStructure(['success', 'status', 'message', 'errors' => ['email']]);
    }

    public function test_user_can_login_view_profile_update_profile_and_logout_revoking_token(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Carlos Rivera',
            'email' => 'carlos.rivera@lifemedic.com',
            'password' => 'ClaveSegura99',
            'password_confirmation' => 'ClaveSegura99',
        ])->assertCreated();

        $login = $this->postJson('/api/auth/login', [
            'email' => 'carlos.rivera@lifemedic.com',
            'password' => 'ClaveSegura99',
            'device_name' => 'iPhone 16',
        ]);

        $login->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.token_type', 'Bearer');

        $token = $login->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/profile')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200)
            ->assertJsonPath('data.user.email', 'carlos.rivera@lifemedic.com');

        $this->withToken($token)
            ->putJson('/api/auth/profile', [
                'name' => 'Carlos A. Rivera',
            ])
            ->assertOk()
            ->assertJsonPath('data.user.name', 'Carlos A. Rivera');

        $this->withToken($token)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', 200);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/auth/profile')
            ->assertStatus(401)
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 401);
    }

    public function test_postman_request_without_accept_header_gets_json_401_on_protected_and_invalid_login(): void
    {
        User::factory()->create([
            'email' => 'cliente@lifemedic.com',
            'password' => 'Correcta123',
        ]);

        $this->post('/api/auth/login', [
            'email' => 'cliente@lifemedic.com',
            'password' => 'Incorrecta000',
        ])->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 401);

        $this->get('/api/auth/profile')
            ->assertStatus(401)
            ->assertHeader('Content-Type', 'application/json')
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 401);
    }

    public function test_deactivated_user_is_blocked_with_403_and_existing_tokens_revoked(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Cuenta Desactivable',
            'email' => 'suspendida@lifemedic.com',
            'password' => 'ClaveSegura99',
            'password_confirmation' => 'ClaveSegura99',
        ])->assertCreated();

        $token = $register->json('data.token');
        $user = User::query()->where('email', 'suspendida@lifemedic.com')->firstOrFail();

        app(UserManagementService::class)->setActiveStatus($user, false);

        $this->app['auth']->forgetGuards();

        $this->withToken($token)
            ->getJson('/api/auth/profile')
            ->assertStatus(401)
            ->assertJsonPath('success', false);

        $this->postJson('/api/auth/login', [
            'email' => 'suspendida@lifemedic.com',
            'password' => 'ClaveSegura99',
        ])->assertStatus(403)
            ->assertJsonPath('success', false)
            ->assertJsonPath('status', 403);
    }

    public function test_mobile_auth_normalizes_uppercase_emails_on_register_and_profile_update(): void
    {
        $this->postJson('/api/auth/register', [
            'name' => 'Ana Pérez',
            'email' => 'Ana.Perez@LifeMedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()
            ->assertJsonPath('data.user.email', 'ana.perez@lifemedic.com');

        $this->postJson('/api/auth/register', [
            'name' => 'Ana Duplicada',
            'email' => 'ANA.PEREZ@LIFEMEDIC.COM',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertStatus(422)
            ->assertJsonValidationErrors('email');

        $secondToken = $this->postJson('/api/auth/register', [
            'name' => 'Luis Gómez',
            'email' => 'luis.gomez@lifemedic.com',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ])->assertCreated()->json('data.token');

        $this->withToken($secondToken)
            ->putJson('/api/auth/profile', [
                'email' => 'ANA.PEREZ@LIFEMEDIC.COM',
            ])->assertStatus(422)
            ->assertJsonValidationErrors('email');
    }

    public function test_mobile_auth_supports_phone_and_address_on_register_and_profile_update(): void
    {
        $register = $this->postJson('/api/auth/register', [
            'name' => 'Elena Morales',
            'email' => 'elena.morales@lifemedic.com',
            'phone' => '7123-4567',
            'address' => 'Colonia Escalón, San Salvador',
            'password' => 'Secreta123!',
            'password_confirmation' => 'Secreta123!',
        ]);

        $register->assertCreated()
            ->assertJsonPath('data.user.phone', '7123-4567')
            ->assertJsonPath('data.user.address', 'Colonia Escalón, San Salvador');

        $token = $register->json('data.token');

        $this->withToken($token)
            ->getJson('/api/auth/profile')
            ->assertOk()
            ->assertJsonPath('data.user.phone', '7123-4567')
            ->assertJsonPath('data.user.address', 'Colonia Escalón, San Salvador');

        $this->withToken($token)
            ->putJson('/api/auth/profile', [
                'phone' => '7999-0000',
                'address' => null,
            ])
            ->assertOk()
            ->assertJsonPath('data.user.phone', '7999-0000')
            ->assertJsonPath('data.user.address', null);

        $this->withToken($token)
            ->putJson('/api/auth/profile', [
                'phone' => str_repeat('9', 31),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('phone');
    }
}
