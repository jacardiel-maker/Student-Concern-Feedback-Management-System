<?php

namespace Tests\Feature;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Models\Attachment;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Reply;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ConcernControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_creates_concern_with_ticket_audit_log_and_admin_notification(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Academic']);

        $response = $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Broken classroom projector',
            'description' => 'The projector in room 201 does not work.',
            'priority' => ConcernPriority::Normal->value,
        ]);

        $concern = Concern::firstOrFail();

        $response->assertRedirectToRoute('concerns.show', $concern);
        $response->assertSessionHas('success', __('messages.concern_created', ['ticket' => $concern->ticket_no]));
        $this->assertMatchesRegularExpression('/^SC-\d{4}-0001$/', $concern->ticket_no);
        $this->assertSame($student->id, $concern->user_id);
        $this->assertSame(ConcernStatus::Pending, $concern->status);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'user_id' => $student->id,
            'action' => 'created',
            'new_value' => ConcernStatus::Pending->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'concern_id' => $concern->id,
            'title' => __('messages.new_concern_title'),
        ]);
    }

    public function test_anonymous_concern_retains_author_identity_for_audited_unmasking(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Anonymous report',
            'description' => 'I would prefer to remain anonymous.',
            'priority' => ConcernPriority::Normal->value,
            'is_anonymous' => true,
        ]);

        $concern = Concern::firstOrFail();

        $this->assertSame($student->id, $concern->user_id);
        $this->assertTrue($concern->isAnonymous());
    }

    public function test_urgent_category_forces_urgent_priority_and_escalation(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->urgent()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Bullying incident',
            'description' => 'This happened after class.',
            'priority' => ConcernPriority::Low->value,
        ]);

        $concern = Concern::firstOrFail();

        $this->assertSame(ConcernPriority::Urgent, $concern->priority);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'urgent_escalated',
            'new_value' => ConcernPriority::Urgent->value,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $admin->id,
            'title' => __('messages.urgent_alert'),
        ]);
    }

    public function test_only_active_students_can_submit_concerns(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $this->actingAs($teacher)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Teacher submission attempt',
            'description' => 'Teachers must use the assignment workflow instead.',
            'priority' => ConcernPriority::Normal->value,
        ])->assertForbidden();

        $this->assertDatabaseCount('concerns', 0);
    }

    public function test_create_form_offers_active_teachers_and_urgent_warning(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();

        $this->actingAs($student)->get(route('concerns.create'))
            ->assertOk()
            ->assertSee('name="recipient_id"', false)
            ->assertSee($teacher->name)
            ->assertSee(__('labels.urgent_category_warning'));
    }

    public function test_student_can_select_an_active_teacher_recipient(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'recipient_id' => $teacher->id,
            'subject' => 'Concern for guidance office',
            'description' => 'I would like this teacher to review my concern.',
            'priority' => ConcernPriority::Normal->value,
        ])->assertRedirect();

        $concern = Concern::firstOrFail();

        $this->assertSame($teacher->id, $concern->recipient_id);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'assigned',
            'new_value' => (string) $teacher->id,
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacher->id,
            'title' => __('messages.assigned'),
        ]);
    }

    public function test_student_cannot_select_a_non_teacher_recipient(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'recipient_id' => $otherStudent->id,
            'subject' => 'Invalid recipient',
            'description' => 'The recipient must be an active teacher.',
            'priority' => ConcernPriority::Normal->value,
        ])->assertSessionHasErrors('recipient_id');

        $this->assertDatabaseCount('concerns', 0);
    }

    public function test_concern_accepts_all_declared_attachment_types_and_rejects_others(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Evidence types',
            'description' => 'Checking spreadsheet and text evidence.',
            'priority' => ConcernPriority::Normal->value,
            'attachments' => [
                UploadedFile::fake()->create('report.xlsx', 12, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'),
                UploadedFile::fake()->create('notes.txt', 5, 'text/plain'),
            ],
        ])->assertRedirect();

        $this->assertSame(2, Attachment::count());

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Invalid evidence',
            'description' => 'Executable files are not allowed.',
            'priority' => ConcernPriority::Normal->value,
            'attachments' => [
                UploadedFile::fake()->create('malware.exe', 12, 'application/octet-stream'),
            ],
        ])->assertSessionHasErrors('attachments.0');
    }

    public function test_urgent_concerns_are_highlighted_and_anonymous_badge_is_shown(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        Concern::factory()->for($student, 'author')->create([
            'category_id' => $category->id,
            'priority' => ConcernPriority::Urgent,
            'is_anonymous' => true,
            'subject' => 'Urgent anonymous concern',
        ]);
        Concern::factory()->for($student, 'author')->create([
            'category_id' => $category->id,
            'priority' => ConcernPriority::Normal,
            'subject' => 'Normal concern',
        ]);

        $this->actingAs($student)->get(route('concerns.index'))
            ->assertOk()
            ->assertSee('bg-clay-50/70', false)
            ->assertSee('urgent-pulse', false)
            ->assertSee(__('labels.anonymous'));
    }

    public function test_submitter_label_hides_anonymous_authors_from_teachers_and_admins(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create(['name' => 'Secret Student']);
        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()
            ->for($student, 'author')
            ->create([
                'is_anonymous' => true,
                'recipient_id' => $teacher->id,
            ]);

        $this->actingAs($teacher)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(__('labels.anonymous'))
            ->assertDontSee('Secret Student');

        $this->actingAs($admin)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(__('labels.anonymous'))
            ->assertDontSee('Secret Student');

        $this->actingAs($student)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee('Secret Student');
    }

    public function test_concern_attachment_is_stored_on_the_private_disk(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $student = User::factory()->student()->create();
        $category = Category::factory()->create();

        $this->actingAs($student)->post(route('concerns.store'), [
            'category_id' => $category->id,
            'subject' => 'Concern with evidence',
            'description' => 'Please see the attached file.',
            'priority' => ConcernPriority::Normal->value,
            'attachments' => [
                UploadedFile::fake()->create('evidence.pdf', 20, 'application/pdf'),
            ],
        ]);

        $attachment = Attachment::firstOrFail();

        $this->assertTrue(str_starts_with($attachment->stored_path, now()->format('Y/m').'/'));
        $this->assertSame('evidence.pdf', $attachment->original_name);
        Storage::disk('attachments')->assertExists($attachment->stored_path);
    }

    public function test_student_sees_only_their_own_concerns(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        Concern::factory()->for($student, 'author')->create(['subject' => 'My visible concern']);
        Concern::factory()->for($otherStudent, 'author')->create(['subject' => 'Other student concern']);

        $this->actingAs($student)->get(route('concerns.index'))
            ->assertOk()
            ->assertSee('My visible concern')
            ->assertDontSee('Other student concern');
    }

    public function test_teacher_and_admin_are_redirected_from_the_student_concern_index(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();

        $this->actingAs($teacher)->get(route('concerns.index'))
            ->assertRedirectToRoute('teacher.assigned.index');

        $this->actingAs($admin)->get(route('concerns.index'))
            ->assertRedirectToRoute('admin.reports.index');
    }

    public function test_student_cannot_view_another_students_concern(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $concern = Concern::factory()->for($otherStudent, 'author')->create();

        $this->actingAs($student)->get(route('concerns.show', $concern))->assertForbidden();
    }

    public function test_teacher_cannot_view_concern_assigned_to_another_teacher(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $otherTeacher->id]);

        $this->actingAs($teacher)->get(route('concerns.show', $concern))->assertForbidden();
    }

    public function test_admin_can_view_any_concern(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()->create();

        $this->actingAs($admin)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(route('admin.reports.index'))
            ->assertDontSee(route('teacher.assigned.index'));
    }

    public function test_student_cannot_update_concern_status(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();

        $this->actingAs($student)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::UnderReview->value,
        ])->assertForbidden();
    }

    public function test_teacher_status_update_is_audited_and_notifies_the_author(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()
            ->for($student, 'author')
            ->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::UnderReview->value,
            'note' => 'Reviewed the initial report.',
        ])->assertRedirect();

        $this->assertSame(ConcernStatus::UnderReview, $concern->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'status_changed',
            'old_value' => ConcernStatus::Pending->value,
            'new_value' => ConcernStatus::UnderReview->value,
            'reason' => 'Reviewed the initial report.',
        ]);
        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'title' => __('messages.status_changed'),
        ]);
    }

    public function test_invalid_status_transition_is_rejected(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()
            ->for($student, 'author')
            ->resolved()
            ->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::Pending->value,
        ])->assertSessionHasErrors('status');

        $this->assertSame(ConcernStatus::Resolved, $concern->fresh()->status);
    }

    public function test_internal_replies_and_their_attachments_are_hidden_from_the_student_author(): void
    {
        $this->seed(RoleSeeder::class);

        Storage::fake('attachments');

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()
            ->for($student, 'author')
            ->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('concerns.reply', $concern), [
            'body' => 'Public reply from teacher',
        ]);

        $this->actingAs($teacher)->post(route('concerns.reply', $concern), [
            'body' => 'Secret internal note',
            'is_internal_note' => true,
        ]);

        $publicReply = Reply::where('body', 'Public reply from teacher')->firstOrFail();
        $internalReply = Reply::where('body', 'Secret internal note')->firstOrFail();
        $this->assertTrue($internalReply->is_internal_note);

        Attachment::factory()->for($concern)->for($publicReply, 'reply')->create([
            'original_name' => 'public-evidence.pdf',
        ]);
        Attachment::factory()->for($concern)->for($internalReply, 'reply')->create([
            'original_name' => 'internal-evidence.pdf',
        ]);

        $this->actingAs($student)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee('Public reply from teacher')
            ->assertSee('public-evidence.pdf')
            ->assertDontSee('Secret internal note')
            ->assertDontSee('internal-evidence.pdf');
    }

    public function test_student_reply_is_always_public(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();

        $this->actingAs($student)->post(route('concerns.reply', $concern), [
            'body' => 'Student public reply',
            'is_internal_note' => true,
        ]);

        $reply = Reply::where('body', 'Student public reply')->firstOrFail();

        $this->assertFalse($reply->is_internal_note);
    }

    public function test_first_public_staff_reply_auto_advances_pending_concern(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()
            ->for($student, 'author')
            ->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('concerns.reply', $concern), [
            'body' => 'We are reviewing this report.',
        ])->assertRedirect();

        $this->assertSame(ConcernStatus::UnderReview, $concern->fresh()->status);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'status_changed',
            'new_value' => ConcernStatus::UnderReview->value,
            'reason' => __('messages.auto_advanced_after_reply'),
        ]);
    }

    public function test_internal_note_does_not_advance_concern_status(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('concerns.reply', $concern), [
            'body' => 'Internal follow-up needed.',
            'is_internal_note' => true,
        ])->assertRedirect();

        $this->assertSame(ConcernStatus::Pending, $concern->fresh()->status);
    }

    public function test_closed_concern_rejects_replies(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->closed()->create();

        $this->actingAs($student)->post(route('concerns.reply', $concern), [
            'body' => 'One more message.',
        ])->assertForbidden();

        $this->assertDatabaseCount('replies', 0);
    }

    public function test_full_ticket_page_shows_conversation_workflow_and_audit_trail(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::UnderReview->value,
        ])->assertRedirect();

        $this->actingAs($teacher)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(__('labels.conversation'))
            ->assertSee(__('labels.audit_trail'))
            ->assertSee(__('labels.mark_as', ['status' => ConcernStatus::InProgress->label()]))
            ->assertSee('name="attachments[]"', false);
    }

    public function test_rating_notifies_the_assigned_teacher(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()
            ->for($student, 'author')
            ->resolved()
            ->create(['recipient_id' => $teacher->id]);

        $this->actingAs($student)->post(route('concerns.rate', $concern), [
            'score' => 4,
        ])->assertRedirectToRoute('concerns.show', $concern);

        $this->assertDatabaseHas('notifications', [
            'user_id' => $teacher->id,
            'concern_id' => $concern->id,
            'title' => __('messages.rating_received'),
        ]);
    }

    public function test_closed_concern_cannot_be_rated(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->closed()->create();

        $this->actingAs($student)->post(route('concerns.rate', $concern), [
            'score' => 5,
        ])->assertSessionHasErrors('score');

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_author_can_rate_a_resolved_concern(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->resolved()->create();

        $this->actingAs($student)->post(route('concerns.rate', $concern), [
            'score' => 5,
            'comment' => 'Very helpful response.',
        ])->assertRedirectToRoute('concerns.show', $concern);

        $this->assertDatabaseHas('ratings', [
            'concern_id' => $concern->id,
            'user_id' => $student->id,
            'score' => 5,
        ]);
        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'action' => 'rated',
            'new_value' => '5',
        ]);
    }

    public function test_non_author_cannot_rate_a_concern(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $author = User::factory()->student()->create();
        $concern = Concern::factory()->for($author, 'author')->resolved()->create();

        $this->actingAs($student)->post(route('concerns.rate', $concern), [
            'score' => 5,
        ])->assertForbidden();
    }

    public function test_rating_a_pending_concern_is_rejected(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();

        $this->actingAs($student)->post(route('concerns.rate', $concern), [
            'score' => 5,
        ])->assertSessionHasErrors('score');

        $this->assertDatabaseCount('ratings', 0);
    }

    public function test_user_supplied_concern_content_is_escaped(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $maliciousSubject = "<script>alert('xss')</script>";
        $concern = Concern::factory()->for($student, 'author')->create(['subject' => $maliciousSubject]);

        $this->actingAs($student)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertDontSee($maliciousSubject, false)
            ->assertSee('&lt;script&gt;', false);
    }
}
