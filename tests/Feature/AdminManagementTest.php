<?php

namespace Tests\Feature;

use App\Enums\ConcernPriority;
use App\Enums\StudentApprovalStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Role;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AdminManagementTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_creates_a_teacher_account(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $teacherRoleId = Role::where('name', 'teacher')->value('id');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Teacher',
            'email' => 'new-teacher@scfms.test',
            'role_id' => $teacherRoleId,
            'department' => 'Guidance Office',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'is_active' => 1,
        ])->assertRedirectToRoute('admin.users.index');

        $this->assertDatabaseHas('users', [
            'name' => 'New Teacher',
            'email' => 'new-teacher@scfms.test',
            'role_id' => $teacherRoleId,
            'is_active' => true,
        ]);
    }

    public function test_admin_user_creation_requires_a_student_number_for_students(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $studentRoleId = Role::where('name', 'student')->value('id');

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'New Student',
            'email' => 'new-student@scfms.test',
            'role_id' => $studentRoleId,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ])->assertSessionHasErrors('student_number');

        $this->assertDatabaseMissing('users', ['email' => 'new-student@scfms.test']);
    }

    public function test_admin_soft_deletes_another_user_but_not_themselves(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $target = User::factory()->student()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee('action="'.route('admin.users.destroy', $target).'"', false)
            ->assertDontSee('action="'.route('admin.users.destroy', $admin).'"', false);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $target))->assertRedirect();

        $this->assertSoftDeleted($target);
        $this->assertFalse(User::withTrashed()->findOrFail($target->id)->is_active);

        $this->actingAs($admin)->delete(route('admin.users.destroy', $admin))->assertForbidden();
    }

    public function test_admin_can_restore_a_soft_deleted_user(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $target = User::factory()->student()->create();
        $target->delete();

        $this->actingAs($admin)->get(route('admin.users.trashed'))
            ->assertOk()
            ->assertSee($target->email);

        $this->actingAs($admin)->post(route('admin.users.restore', $target))->assertRedirectToRoute('admin.users.index');

        $this->assertNotSoftDeleted($target);
        $this->assertTrue($target->fresh()->is_active);
    }

    public function test_admin_creates_a_category(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->post(route('admin.categories.store'), [
            'name' => 'Housing',
            'name_fil' => 'Pabahay',
            'is_urgent' => 1,
            'is_active' => 1,
        ])->assertRedirectToRoute('admin.categories.index');

        $this->assertDatabaseHas('categories', [
            'name' => 'Housing',
            'name_fil' => 'Pabahay',
            'is_urgent' => true,
            'is_active' => true,
        ]);
    }

    public function test_deleting_a_category_in_use_deactivates_it_instead(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();
        Concern::factory()->create(['category_id' => $category->id]);

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();

        $this->assertFalse($category->fresh()->is_active);
        $this->assertModelExists($category);
    }

    public function test_deleting_an_unused_category_removes_it(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create();

        $this->actingAs($admin)->delete(route('admin.categories.destroy', $category))->assertRedirect();

        $this->assertModelMissing($category);
    }

    public function test_admin_assigns_a_concern_to_a_teacher(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create();

        $this->actingAs($admin)->post(route('admin.assignments.store', $concern), [
            'recipient_id' => $teacher->id,
        ])->assertRedirect();

        $this->assertSame($teacher->id, $concern->fresh()->recipient_id);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'assigned',
            'new_value' => (string) $teacher->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacher->id,
            'concern_id' => $concern->id,
            'type' => 'assigned',
        ]);
    }

    public function test_admin_cannot_assign_a_concern_to_a_student(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $concern = Concern::factory()->create();

        $this->actingAs($admin)->post(route('admin.assignments.store', $concern), [
            'recipient_id' => $student->id,
        ])->assertSessionHasErrors('recipient_id');

        $this->assertNull($concern->fresh()->recipient_id);
    }

    public function test_admin_unassigns_a_concern(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id]);

        $this->actingAs($admin)->delete(route('admin.assignments.destroy', $concern))->assertRedirect();

        $this->assertNull($concern->fresh()->recipient_id);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'unassigned',
            'old_value' => (string) $teacher->id,
        ]);
    }

    public function test_audit_log_index_renders_matching_logs(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()->create(['ticket_no' => 'SC-2026-0099']);
        AuditLog::factory()->for($concern)->for($admin, 'user')->create([
            'action' => 'identity_unmasked',
            'reason' => 'Safety review',
            'ip_address' => '127.0.0.1',
        ]);

        $this->actingAs($admin)->get(route('admin.audit-logs.index', ['action' => 'identity_unmasked']))
            ->assertOk()
            ->assertSee('identity_unmasked')
            ->assertSee('SC-2026-0099')
            ->assertSee('Safety review')
            ->assertSee('127.0.0.1');
    }

    public function test_admin_can_unmask_only_urgent_anonymous_concern_with_reason(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $author = User::factory()->student()->create(['name' => 'Hidden Student']);
        $urgentCategory = Category::factory()->create(['is_urgent' => true, 'is_active' => true]);
        $concern = Concern::factory()->for($author, 'author')->for($urgentCategory, 'category')->create([
            'is_anonymous' => true,
            'priority' => ConcernPriority::Urgent,
        ]);

        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), [
            'reason' => 'Bad',
        ])->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), [
            'reason' => 'Needed for safety investigation.',
        ])->assertOk()->assertSee($author->name);

        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'identity_unmasked',
            'new_value' => $author->name,
        ]);
    }

    public function test_admin_cannot_unmask_non_safety_anonymous_concern(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $author = User::factory()->student()->create();
        $category = Category::factory()->create(['is_urgent' => false, 'is_active' => true]);
        $concern = Concern::factory()->for($author, 'author')->for($category, 'category')->create([
            'is_anonymous' => true,
            'priority' => ConcernPriority::Normal,
        ]);

        $this->actingAs($admin)->get(route('admin.unmask.show', $concern))->assertForbidden();
        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), [
            'reason' => 'Valid enough reason',
        ])->assertForbidden();
    }

    public function test_admin_can_review_and_approve_a_google_student_account(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $pendingStudent = User::factory()->student()->create([
            'name' => 'Pending Student',
            'email' => 'pending.student@scfms.test',
            'student_number' => 'ST-2026-001',
            'google_id' => 'google-123',
            'is_active' => false,
            'approval_status' => StudentApprovalStatus::Pending,
        ]);
        $activeStudent = User::factory()->student()->create();

        $this->actingAs($admin)->get(route('admin.users.index', ['approval' => 'pending']))
            ->assertOk()
            ->assertSee('Pending Student')
            ->assertSee(__('labels.awaiting_approval'))
            ->assertSee('action="'.route('admin.users.approve', $pendingStudent).'"', false)
            ->assertDontSee($activeStudent->name);

        $this->actingAs($admin)->post(route('admin.users.approve', $pendingStudent))
            ->assertRedirect()
            ->assertSessionHas('success', __('messages.user_approved'));

        $this->assertTrue($pendingStudent->fresh()->is_active);
        $this->assertSame(StudentApprovalStatus::Approved, $pendingStudent->fresh()->approval_status);
        $this->assertDatabaseHas('audit_logs', [
            'action' => 'user_approved',
            'user_id' => $admin->id,
            'new_value' => 'approved',
        ]);
    }

    public function test_admin_cannot_approve_an_account_that_is_not_pending(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();

        $this->actingAs($admin)->post(route('admin.users.approve', $student))->assertNotFound();

        $this->assertTrue($student->fresh()->is_active);
        $this->assertNull($student->fresh()->approval_status);
    }

    public function test_non_admin_cannot_approve_a_student_account(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create([
            'is_active' => false,
            'approval_status' => StudentApprovalStatus::Pending,
        ]);

        $this->actingAs($student)->post(route('admin.users.approve', $otherStudent))->assertForbidden();

        $this->assertTrue($otherStudent->fresh()->isAwaitingApproval());
    }
}
