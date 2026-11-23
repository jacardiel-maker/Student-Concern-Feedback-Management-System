<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RoleRestrictionTest extends TestCase
{
    use LazilyRefreshDatabase;

    /**
     * @return array<string, array{string}>
     */
    public static function nonAdminRoles(): array
    {
        return [
            'student' => ['student'],
            'teacher' => ['teacher'],
        ];
    }

    /**
     * @return array<string, array{string}>
     */
    public static function nonTeacherRoles(): array
    {
        return [
            'student' => ['student'],
            'admin' => ['admin'],
        ];
    }

    #[DataProvider('nonAdminRoles')]
    public function test_admin_routes_forbid_other_roles(string $role): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->{$role}()->create();

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->actingAs($user)->get(route('admin.reports.index'))->assertForbidden();
    }

    #[DataProvider('nonTeacherRoles')]
    public function test_teacher_routes_forbid_other_roles(string $role): void
    {
        $this->seed(RoleSeeder::class);

        $user = User::factory()->{$role}()->create();

        $this->actingAs($user)->get(route('teacher.assigned.index'))->assertForbidden();
    }

    public function test_guest_is_redirected_to_login_from_a_role_protected_route(): void
    {
        $this->get(route('admin.users.index'))->assertRedirectToRoute('login.show');
    }
}
