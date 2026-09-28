<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Welcome back');
    }

    public function test_registration_screen_can_be_rendered(): void
    {
        $response = $this->get('/register');
        $response->assertStatus(200);
        $response->assertSee('Create account');
    }

    public function test_users_can_register_with_onboarding_data(): void
    {
        $response = $this->post('/register', [
            'name' => 'Hunzala Khan',
            'email' => 'hunzala@campuscoin.edu',
            'password' => 'SecretPassword123!',
            'password_confirmation' => 'SecretPassword123!',
            'academic_year' => 'Year 3',
            'monthly_allowance' => 35000,
            'savings_goal' => 7000,
            'university' => 'NUST',
            'program' => 'Computer Science',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');

        $this->assertDatabaseHas('users', [
            'name' => 'Hunzala Khan',
            'email' => 'hunzala@campuscoin.edu',
            'academic_year' => 'Year 3',
            'monthly_allowance' => 35000.00,
            'savings_goal' => 7000.00,
        ]);

        $user = User::where('email', 'hunzala@campuscoin.edu')->first();
        $this->assertTrue(Hash::check('SecretPassword123!', $user->password));
    }

    public function test_registration_validation_rules(): void
    {
        $response = $this->post('/register', [
            'name' => '',
            'email' => 'invalid-email',
            'password' => 'short',
            'password_confirmation' => 'mismatch',
            'monthly_allowance' => -500,
            'savings_goal' => -100,
        ]);

        $response->assertSessionHasErrors([
            'name',
            'email',
            'password',
            'monthly_allowance',
            'savings_goal',
        ]);
        $this->assertGuest();
    }

    public function test_users_can_authenticate_using_the_login_screen(): void
    {
        $user = User::factory()->create([
            'email' => 'student@campuscoin.edu',
            'password' => Hash::make('password123'),
        ]);

        $response = $this->post('/login', [
            'email' => 'student@campuscoin.edu',
            'password' => 'password123',
        ]);

        $this->assertAuthenticated();
        $response->assertRedirect('/dashboard');
    }

    public function test_users_can_not_authenticate_with_invalid_password(): void
    {
        $user = User::factory()->create([
            'email' => 'student@campuscoin.edu',
            'password' => Hash::make('password123'),
        ]);

        $this->post('/login', [
            'email' => 'student@campuscoin.edu',
            'password' => 'wrong-password',
        ]);

        $this->assertGuest();
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/login');
    }

    public function test_unauthenticated_users_are_redirected_from_protected_routes(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
        $this->get('/profile')->assertRedirect('/login');
        $this->get('/settings')->assertRedirect('/login');
    }

    public function test_authenticated_users_cannot_access_guest_routes(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/login')->assertRedirect('/dashboard');
        $this->actingAs($user)->get('/register')->assertRedirect('/dashboard');
    }

    public function test_password_reset_link_can_be_requested(): void
    {
        $user = User::factory()->create([
            'email' => 'test@campuscoin.edu',
        ]);

        $response = $this->post('/forgot-password', [
            'email' => 'test@campuscoin.edu',
        ]);

        $response->assertSessionHas('status');
        $this->assertDatabaseHas('password_reset_tokens', [
            'email' => 'test@campuscoin.edu',
        ]);
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'test@campuscoin.edu',
            'password' => Hash::make('OldPassword123'),
        ]);

        $token = Password::createToken($user);

        $response = $this->post('/reset-password', [
            'token' => $token,
            'email' => 'test@campuscoin.edu',
            'password' => 'NewPassword123!',
            'password_confirmation' => 'NewPassword123!',
        ]);

        $response->assertRedirect('/login');
        $this->assertTrue(Hash::check('NewPassword123!', $user->fresh()->password));
    }
}
