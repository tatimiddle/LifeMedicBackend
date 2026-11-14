<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login_and_authenticated_admin_gets_dashboard(): void
    {
        $this->get('/')->assertRedirect(route('login'));
        $this->get('/login')->assertOk();

        $this->actingAsAdmin();

        $this->get('/')->assertOk();
    }
}
