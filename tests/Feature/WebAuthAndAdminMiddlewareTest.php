<?php

namespace Tests\Feature;

use App\Services\UserManagementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebAuthAndAdminMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_renders_split_screen_brand_logo_mockup_and_aqua_aaa_palette(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('Inicia sesión en tu cuenta')
            ->assertSee('lifemedic-logo-dark.png')
            ->assertSee('lifemedic-logo-light.png')
            ->assertSee('login-tablet-mockup.png')
            ->assertSee('#095d52')
            ->assertSee('Iniciar sesión')
            ->assertDontSee('#165dff')
            ->assertDontSee('#256eff');
    }

    public function test_guest_users_are_redirected_to_login_across_protected_admin_routes(): void
    {
        $protected = [
            '/',
            '/medicamentos',
            '/categorias',
            '/facturas',
            '/ventas',
            '/productos',
        ];

        foreach ($protected as $uri) {
            $this->get($uri)->assertRedirect(route('login'));
        }
    }

    public function test_non_admin_user_cannot_sign_in_or_access_admin_panel(): void
    {
        $users = app(UserManagementService::class);
        $client = $users->createUser([
            'name' => 'Paciente App',
            'email' => 'paciente@lifemedic.com',
            'password' => 'SecretPass123!',
            'role' => 'cliente',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => 'paciente@lifemedic.com',
            'password' => 'SecretPass123!',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($client)
            ->get('/')
            ->assertStatus(403);

        $this->actingAs($client)
            ->get(route('medicamentos.index'))
            ->assertStatus(403);

        $this->actingAs($client)
            ->get(route('categorias.index'))
            ->assertStatus(403);
    }

    public function test_inactive_admin_is_blocked_from_signing_in_and_accessing_panel(): void
    {
        $users = app(UserManagementService::class);
        $inactiveAdmin = $users->createUser([
            'name' => 'Admin Suspendido',
            'email' => 'suspendido@lifemedic.com',
            'password' => 'SecretPass123!',
            'role' => 'administrador',
            'is_active' => false,
        ]);

        $this->post(route('login.store'), [
            'email' => 'suspendido@lifemedic.com',
            'password' => 'SecretPass123!',
        ])->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertGuest();

        $this->actingAs($inactiveAdmin)
            ->get('/')
            ->assertRedirect(route('login'));
    }

    public function test_active_admin_can_login_access_panel_and_logout(): void
    {
        $users = app(UserManagementService::class);
        $admin = $users->createUser([
            'name' => 'Dra. Sofía Directora',
            'email' => 'directora@lifemedic.com',
            'password' => 'AdminClave2026!',
            'role' => 'administrador',
            'is_active' => true,
        ]);

        $this->post(route('login.store'), [
            'email' => 'directora@lifemedic.com',
            'password' => 'AdminClave2026!',
            'remember' => '1',
        ])->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($admin);

        $this->get('/')
            ->assertOk()
            ->assertSee('Dra. Sofía Directora');

        $this->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
        $this->get('/')->assertRedirect(route('login'));
    }
}
