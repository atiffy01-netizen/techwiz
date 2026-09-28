<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_can_be_rendered_with_authenticated_user_data(): void
    {
        $user = User::factory()->create([
            'name' => 'Sara Ahmed',
            'email' => 'sara@campuscoin.edu',
            'academic_year' => 'Year 2',
            'monthly_allowance' => 40000.00,
            'savings_goal' => 8000.00,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertStatus(200);
        $response->assertSee('Sara Ahmed');
        $response->assertSee('sara@campuscoin.edu');
        $response->assertSee('40,000.00');
        $response->assertSee('8,000.00');
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@campuscoin.edu',
            'academic_year' => 'Year 1',
            'monthly_allowance' => 20000.00,
            'savings_goal' => 3000.00,
        ]);

        $response = $this->actingAs($user)->post('/profile', [
            'name' => 'Updated Name',
            'email' => 'updated@campuscoin.edu',
            'academic_year' => 'Year 4',
            'monthly_allowance' => 50000.00,
            'savings_goal' => 12000.00,
            'university' => 'NUST',
            'program' => 'Software Engineering',
        ]);

        $response->assertRedirect('/profile');
        $response->assertSessionHas('success');

        $user->refresh();

        $this->assertSame('Updated Name', $user->name);
        $this->assertSame('updated@campuscoin.edu', $user->email);
        $this->assertSame('Year 4', $user->academic_year);
        $this->assertEquals(50000.00, (float) $user->monthly_allowance);
        $this->assertEquals(12000.00, (float) $user->savings_goal);
    }

    public function test_profile_email_must_be_unique_across_users(): void
    {
        $userA = User::factory()->create(['email' => 'usera@campuscoin.edu']);
        $userB = User::factory()->create(['email' => 'userb@campuscoin.edu']);

        $response = $this->actingAs($userA)->post('/profile', [
            'name' => 'User A',
            'email' => 'userb@campuscoin.edu', // taken by User B
        ]);

        $response->assertSessionHasErrors('email');
    }

    public function test_user_can_update_password_with_valid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentSecret123'),
        ]);

        $response = $this->actingAs($user)->post('/settings/password', [
            'current_password' => 'CurrentSecret123',
            'password' => 'BrandNewSecret456!',
            'password_confirmation' => 'BrandNewSecret456!',
        ]);

        $response->assertRedirect('/settings');
        $response->assertSessionHas('success');

        $this->assertTrue(Hash::check('BrandNewSecret456!', $user->fresh()->password));
    }

    public function test_user_cannot_update_password_with_invalid_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('CurrentSecret123'),
        ]);

        $response = $this->actingAs($user)->post('/settings/password', [
            'current_password' => 'WrongPassword',
            'password' => 'BrandNewSecret456!',
            'password_confirmation' => 'BrandNewSecret456!',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('CurrentSecret123', $user->fresh()->password));
    }

    public function test_dashboard_displays_authenticated_user_name(): void
    {
        $user = User::factory()->create([
            'name' => 'Hunzala TestUser',
        ]);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertSee('Hunzala TestUser');
    }
}
