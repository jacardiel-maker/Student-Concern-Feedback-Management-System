<?php

namespace Tests\Feature;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Models\Category;
use App\Models\Concern;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConcernTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_submit_concern_creates_ticket_and_pending_status(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $response = $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Test concern',
            'description' => 'Description here',
            'priority' => ConcernPriority::Normal->value,
        ]);

        $concern = Concern::firstOrFail();
        $response->assertRedirectToRoute('concerns.show', $concern);
        $this->assertMatchesRegularExpression('/^SC-\d{4}-\d{4}$/', $concern->ticket_no);
        $this->assertSame(ConcernStatus::Pending, $concern->status);
    }

    public function test_ticket_numbers_increment_sequentially(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();
        $payload = [
            'category_id' => $category->id,
            'subject' => 'Ticket sequence',
            'description' => 'Description here',
            'priority' => ConcernPriority::Normal->value,
        ];

        $this->actingAs($student)->post(route('concerns.store'), $payload);
        $this->actingAs($student)->post(route('concerns.store'), $payload);

        $tickets = Concern::orderBy('id')->pluck('ticket_no')->all();
        $this->assertMatchesRegularExpression('/^SC-\d{4}-0001$/', $tickets[0]);
        $this->assertMatchesRegularExpression('/^SC-\d{4}-0002$/', $tickets[1]);
    }

    public function test_urgent_category_forces_urgent_priority_and_notifies_admins(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->urgent()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Urgent bullying',
            'description' => 'Needs escalation',
            'priority' => ConcernPriority::Low->value,
        ])->assertRedirect();

        $concern = Concern::firstOrFail();
        $this->assertSame(ConcernPriority::Urgent, $concern->priority);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'concern_id' => $concern->id,
            'title' => __('messages.urgent_alert'),
        ]);
    }

    public function test_teachers_cannot_submit_concerns(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $this->actingAs($teacher)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Teacher attempt',
            'description' => 'Should fail',
            'priority' => ConcernPriority::Normal->value,
        ])->assertForbidden();
    }

    public function test_attachments_stored_under_random_names_never_original(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('attachments');
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'With attachment',
            'description' => 'File attached',
            'priority' => ConcernPriority::Normal->value,
            'attachments' => [UploadedFile::fake()->create('original-name.pdf', 20, 'application/pdf')],
        ])->assertRedirect();

        $attachment = Concern::firstOrFail()->attachments()->firstOrFail();
        $this->assertSame('original-name.pdf', $attachment->original_name);
        $this->assertStringNotContainsString('original-name', $attachment->stored_path);
        Storage::disk('attachments')->assertExists($attachment->stored_path);
    }

    public function test_oversized_attachment_rejected(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('attachments');
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Too big',
            'description' => 'File attached',
            'priority' => ConcernPriority::Normal->value,
            'attachments' => [UploadedFile::fake()->create('big.pdf', 5121, 'application/pdf')],
        ])->assertSessionHasErrors('attachments.0');
    }

    public function test_disallowed_file_type_rejected(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('attachments');
        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Bad type',
            'description' => 'File attached',
            'priority' => ConcernPriority::Normal->value,
            'attachments' => [UploadedFile::fake()->create('malware.exe', 10, 'application/octet-stream')],
        ])->assertSessionHasErrors('attachments.0');
    }

    public function test_student_sees_only_their_own_tickets_in_list(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $other = User::factory()->student()->create();
        Concern::factory()->for($student, 'author')->create(['subject' => 'Mine']);
        Concern::factory()->for($other, 'author')->create(['subject' => 'Not mine']);

        $this->actingAs($student)->get(route('concerns.index'))
            ->assertOk()
            ->assertSee('Mine')
            ->assertDontSee('Not mine');
    }

    public function test_teacher_sees_only_assigned_tickets(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        Concern::factory()->create(['recipient_id' => $teacher->id, 'subject' => 'Assigned to me']);
        Concern::factory()->create(['recipient_id' => $otherTeacher->id, 'subject' => 'Assigned to other']);
        Concern::factory()->create(['recipient_id' => null, 'subject' => 'Unassigned concern']);

        $this->actingAs($teacher)->get(route('teacher.assigned.index'))
            ->assertOk()
            ->assertSee('Assigned to me')
            ->assertDontSee('Assigned to other')
            ->assertDontSee('Unassigned concern');
    }

    public function test_anonymous_concern_shows_anonymous_and_hides_student_name_from_teacher(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create(['name' => 'Secret Student']);
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->for($student, 'author')->anonymous()->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(__('labels.anonymous'))
            ->assertDontSee('Secret Student');
    }

    public function test_unassigned_teacher_gets_403_opening_a_concern(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $otherTeacher->id]);

        $this->actingAs($teacher)->get(route('concerns.show', $concern))->assertForbidden();
    }

    public function test_non_participant_gets_403_downloading_attachment(): void
    {
        $this->seed(RoleSeeder::class);
        Storage::fake('attachments');
        $owner = User::factory()->student()->create();
        $outsider = User::factory()->student()->create();
        $concern = Concern::factory()->for($owner, 'author')->create();
        $path = UploadedFile::fake()->create('evidence.pdf', 10, 'application/pdf')->store('2026/01', 'attachments');
        $attachment = $concern->attachments()->create([
            'user_id' => $owner->id,
            'original_name' => 'evidence.pdf',
            'stored_path' => $path,
            'mime_type' => 'application/pdf',
            'size_bytes' => 10,
        ]);

        $this->actingAs($outsider)->get(route('attachments.download', $attachment))->assertForbidden();
    }

    public function test_staff_advance_status_one_step(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id, 'status' => ConcernStatus::Pending]);

        $this->actingAs($teacher)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::UnderReview->value,
        ])->assertRedirect();

        $this->assertSame(ConcernStatus::UnderReview, $concern->fresh()->status);
    }

    public function test_students_cannot_change_status(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();

        $this->actingAs($student)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::UnderReview->value,
        ])->assertForbidden();
    }

    public function test_status_transitions_cannot_skip_steps(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id, 'status' => ConcernStatus::Pending]);

        $this->actingAs($teacher)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::Resolved->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(ConcernStatus::Pending, $concern->fresh()->status);
    }

    public function test_resolving_sets_resolved_at_and_student_can_rate_once(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->for($student, 'author')->create([
            'recipient_id' => $teacher->id,
            'status' => ConcernStatus::InProgress,
        ]);

        $this->actingAs($teacher)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::Resolved->value,
        ])->assertRedirect();

        $concern->refresh();
        $this->assertNotNull($concern->resolved_at);

        $this->actingAs($student)->post(route('concerns.rate', $concern), ['score' => 5])->assertRedirect();
        $this->actingAs($student)->post(route('concerns.rate', $concern), ['score' => 4])->assertSessionHasErrors('score');
        $this->assertDatabaseCount('ratings', 1);
    }

    public function test_rating_rejected_while_not_resolved(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create(['status' => ConcernStatus::Pending]);

        $this->actingAs($student)->post(route('concerns.rate', $concern), ['score' => 5])
            ->assertSessionHasErrors('score');
    }

    public function test_internal_notes_visible_to_staff_invisible_to_students(): void
    {
        $this->seed(RoleSeeder::class);
        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->for($student, 'author')->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('concerns.reply', $concern), [
            'body' => 'Secret internal note',
            'is_internal_note' => true,
        ])->assertRedirect();

        $this->actingAs($teacher)->get(route('concerns.show', $concern))->assertOk()->assertSee('Secret internal note');
        $this->actingAs($student)->get(route('concerns.show', $concern))->assertOk()->assertDontSee('Secret internal note');
    }

    public function test_unmask_requires_real_reason_and_writes_audit_entry(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->admin()->create();
        $author = User::factory()->student()->create(['name' => 'Hidden Student']);
        $category = Category::factory()->urgent()->create();
        $concern = Concern::factory()->for($author, 'author')->for($category)->anonymous()->create([
            'priority' => ConcernPriority::Urgent,
        ]);

        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), ['reason' => 'bad'])
            ->assertSessionHasErrors('reason');

        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), ['reason' => 'Required for safety investigation.'])
            ->assertOk();

        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'identity_unmasked',
            'new_value' => 'Hidden Student',
        ]);
    }

    public function test_unmask_blocked_for_non_urgent_anonymous_concerns(): void
    {
        $this->seed(RoleSeeder::class);
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['is_urgent' => false]);
        $concern = Concern::factory()->for($category)->anonymous()->create(['priority' => ConcernPriority::Normal]);

        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), [
            'reason' => 'Valid reason here',
        ])->assertForbidden();
    }

    public function test_teachers_cannot_unmask(): void
    {
        $this->seed(RoleSeeder::class);
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->urgent()->create();
        $concern = Concern::factory()->for($category)->anonymous()->create(['priority' => ConcernPriority::Urgent]);

        $this->actingAs($teacher)->get(route('admin.unmask.show', $concern))->assertForbidden();
        $this->actingAs($teacher)->post(route('admin.unmask.store', $concern), ['reason' => 'Valid reason here'])
            ->assertForbidden();
    }
}
