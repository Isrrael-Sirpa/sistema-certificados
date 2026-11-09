<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Acceso Administrativo');
    }

    public function test_admin_user_can_authenticate(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@universidad.edu',
            'password' => 'Admin*2026',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_user_cannot_authenticate_with_invalid_password(): void
    {
        $response = $this->post('/login', [
            'email' => 'admin@universidad.edu',
            'password' => 'WrongPassword',
        ]);

        $this->assertGuest();
    }

    public function test_user_can_logout(): void
    {
        $user = User::first();
        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_login_blocks_repeated_failures_for_the_same_account(): void
    {
        $credentials = ['email' => 'admin@universidad.edu', 'password' => 'WrongPassword'];
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('/login', $credentials)->assertSessionHasErrors('email');
        }

        $this->post('/login', [...$credentials, 'password' => 'Admin*2026'])
            ->assertSessionHasErrors(['email' => 'Demasiados intentos de inicio de sesión. Vuelva a intentarlo en un minuto.']);
        $this->assertGuest();
    }

    public function test_an_inactive_account_loses_access_with_an_existing_session(): void
    {
        $user = User::firstOrFail();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $user->update(['is_active' => false]);

        $this->get(route('dashboard'))->assertRedirect(route('login'));
        $this->assertGuest();
    }
}
