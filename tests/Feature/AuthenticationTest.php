<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\DemoDataSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_guest_is_redirected_to_login_when_opening_dashboard(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirectToRoute('login.show');
    }

    public function test_registration_creates_a_student_account_and_signs_them_in(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);

        $response = $this->post(route('register.store'), [
            'name' => 'New Student',
            'email' => 'new-student@scfms.test',
            'student_number' => 'ST-10001',
            'department' => 'Computer Science',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'new-student@scfms.test')->firstOrFail();

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'student_number' => 'ST-10001',
            'department' => 'Computer Science',
            'is_active' => true,
        ]);
        $this->assertTrue($user->isStudent());
    }

    public function test_seeded_default_accounts_can_sign_in(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class, DemoDataSeeder::class]);

        $this->post(route('login.store'), [
            'email' => 'admin@scfms.test',
            'password' => 'admin123',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticated();
        $this->post(route('logout'))->assertRedirectToRoute('login.show');

        $this->post(route('login.store'), [
            'email' => 'j.santos@scfms.test',
            'password' => 'teacher123',
        ])->assertRedirectToRoute('dashboard');

        $this->assertAuthenticated();
    }

    public function test_login_rejects_invalid_credentials(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        User::factory()->student()->create([
            'email' => 'student@scfms.test',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'student@scfms.test',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_inactive_account_cannot_sign_in(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        User::factory()->student()->inactive()->create([
            'email' => 'inactive@scfms.test',
        ]);

        $response = $this->post(route('login.store'), [
            'email' => 'inactive@scfms.test',
            'password' => 'password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_can_sign_out(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $user = User::factory()->student()->create();

        $response = $this->actingAs($user)->post(route('logout'));

        $response->assertRedirectToRoute('login.show');
        $this->assertGuest();
    }
}
