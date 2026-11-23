<?php

namespace Tests\Feature;

use App\Models\Concern;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_registration_creates_a_student_hashes_password_and_logs_in(): void
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

        $response->assertRedirectToRoute('dashboard');

        $user = User::where('email', 'new-student@scfms.test')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertTrue($user->isStudent());
        $this->assertTrue(Hash::check('password123', $user->password));
        $this->assertNotSame('password123', $user->password);
    }

    public function test_valid_login_succeeds(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $user = User::factory()->student()->create(['password' => 'password123']);

        $response = $this->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'password123',
        ]);

        $response->assertRedirectToRoute('dashboard');
        $this->assertAuthenticatedAs($user);
    }

    public function test_teachers_land_on_dashboard_after_login(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->teacher()->create(['password' => 'password123']);

        $this->post(route('login.store'), [
            'email' => $teacher->email,
            'password' => 'password123',
        ])->assertRedirectToRoute('dashboard');
    }

    public function test_login_lands_on_dashboard_even_after_visiting_a_ticket(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $teacher = User::factory()->teacher()->create(['password' => 'password123']);
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->get(route('teacher.assigned.show', $concern))->assertOk();
        $this->post(route('logout'));

        $this->assertSame($concern->id, $teacher->fresh()->last_visited_concern_id);

        $this->post(route('login.store'), [
            'email' => $teacher->email,
            'password' => 'password123',
        ])->assertRedirectToRoute('dashboard');
    }

    public function test_login_ignores_an_intended_url_and_lands_on_dashboard(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $user = User::factory()->student()->create(['password' => 'password123']);

        $this->withSession(['url.intended' => route('concerns.create')])
            ->post(route('login.store'), [
                'email' => $user->email,
                'password' => 'password123',
            ])->assertRedirectToRoute('dashboard');
    }

    public function test_wrong_password_fails_with_generic_error(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $user = User::factory()->student()->create(['password' => 'password123']);

        $response = $this->from(route('login.show'))->post(route('login.store'), [
            'email' => $user->email,
            'password' => 'wrong-password',
        ]);

        $response->assertRedirect(route('login.show'));
        $response->assertSessionHasErrors('email');
        $this->assertSame(__('messages.login_failed'), session('errors')->get('email')[0]);
        $this->assertGuest();
    }

    public function test_inactive_accounts_cannot_log_in(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        User::factory()->student()->inactive()->create([
            'email' => 'inactive@scfms.test',
            'password' => 'password123',
        ]);

        $response = $this->from(route('login.show'))->post(route('login.store'), [
            'email' => 'inactive@scfms.test',
            'password' => 'password123',
        ]);

        $response->assertRedirect(route('login.show'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_authenticated_user_visiting_login_is_redirected_to_dashboard(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $user = User::factory()->student()->create();

        $this->actingAs($user)->get(route('login.show'))
            ->assertRedirectToRoute('dashboard');
    }

    public function test_guests_are_redirected_from_concerns(): void
    {
        $this->get(route('concerns.index'))->assertRedirectToRoute('login.show');
    }

    public function test_students_get_403_on_admin_pages(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_teachers_get_403_on_admin_pages(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($teacher)->get(route('admin.reports.index'))->assertForbidden();
    }

    public function test_admins_can_open_admin_pages(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.reports.index'))->assertOk();
    }
}
