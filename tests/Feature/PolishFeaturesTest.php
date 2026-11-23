<?php

namespace Tests\Feature;

use App\Enums\ConcernStatus;
use App\Models\AuditLog;
use App\Models\Category;
use App\Models\Concern;
use App\Models\Notification;
use App\Models\Rating;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;
use ZipArchive;

class PolishFeaturesTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
    }

    public function test_login_is_limited_to_five_attempts_per_minute(): void
    {
        foreach (range(1, 5) as $attempt) {
            $this->from(route('login.show'))->post(route('login.store'), [
                'email' => 'rate-limit@scfms.test',
                'password' => 'wrong-password',
            ])->assertRedirect(route('login.show'));
        }

        $this->from(route('login.show'))->post(route('login.store'), [
            'email' => 'rate-limit@scfms.test',
            'password' => 'wrong-password',
        ])->assertStatus(429);

        $this->app['cache']->store()->flush();
    }

    public function test_session_keep_alive_is_authenticated_and_records_activity(): void
    {
        $this->post(route('session.keep-alive'))->assertRedirect(route('login.show'));

        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->postJson(route('session.keep-alive'))
            ->assertNoContent();

        $this->assertNotNull(session('session_keep_alive_at'));
    }

    public function test_suggestions_return_only_visible_matching_concerns(): void
    {
        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        $matching = Concern::factory()->for($student, 'author')->create([
            'subject' => 'Broken classroom projector',
        ]);
        Concern::factory()->for($otherStudent, 'author')->create([
            'subject' => 'Scholarship application form',
        ]);

        $this->actingAs($student)
            ->getJson(route('concerns.suggestions', ['subject' => 'projector']))
            ->assertOk()
            ->assertJsonPath('suggestions.0.ticket_no', $matching->ticket_no)
            ->assertJsonCount(1, 'suggestions');
    }

    public function test_suggestions_validate_the_subject_term(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->getJson(route('concerns.suggestions', ['subject' => 'ab']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('subject');
    }

    public function test_create_page_renders_character_counter_templates_and_suggestion_panel(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('concerns.create'))
            ->assertOk()
            ->assertSee('data-character-counter-input', false)
            ->assertSee('data-similar-concerns', false)
            ->assertSee('data-concern-template', false)
            ->assertSee(__('templates.general.label'))
            ->assertSee('5,000');
    }

    public function test_ticket_page_renders_breadcrumb_sticky_actions_and_timeline(): void
    {
        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();
        AuditLog::factory()->for($concern)->for($student, 'user')->create([
            'action' => 'created',
        ]);

        $this->actingAs($student)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(__('labels.my_concerns'))
            ->assertSee($concern->ticket_no)
            ->assertSee('top-16', false)
            ->assertSee(__('labels.print_ticket'))
            ->assertSee(__('labels.audit_trail'))
            ->assertSee('<time', false);
    }

    public function test_students_do_not_see_internal_note_events_in_the_timeline(): void
    {
        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();

        AuditLog::factory()->for($concern)->for($student, 'user')->create([
            'action' => 'internal_note_added',
        ]);
        AuditLog::factory()->for($concern)->for($student, 'user')->create([
            'action' => 'status_changed',
            'new_value' => ConcernStatus::UnderReview->value,
        ]);

        $this->actingAs($student)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(__('audit_actions.status_changed'))
            ->assertDontSee(__('audit_actions.internal_note_added'));
    }

    public function test_close_action_requires_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()->resolved()->create();

        $this->actingAs($admin)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee(__('labels.confirm_close_title'))
            ->assertSee('data-confirm', false)
            ->assertSee('data-confirm-modal', false);
    }

    public function test_deactivate_action_requires_confirmation(): void
    {
        $admin = User::factory()->admin()->create();
        $target = User::factory()->student()->create();

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()
            ->assertSee(__('labels.confirm_deactivate_title'))
            ->assertSee($target->name);
    }

    public function test_admin_dashboard_renders_category_status_heatmap(): void
    {
        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Facilities']);
        Concern::factory()->for($category)->create([
            'status' => ConcernStatus::Pending,
        ]);

        $this->actingAs($admin)->get(route('dashboard'))
            ->assertOk()
            ->assertSee(__('labels.category_status_heatmap'))
            ->assertSee('Facilities');
    }

    public function test_reports_render_monthly_submissions_chart(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)->get(route('admin.reports.index'))
            ->assertOk()
            ->assertSee(__('labels.monthly_submissions'))
            ->assertSee('monthlyChart', false)
            ->assertSee(__('labels.teacher_satisfaction'));
    }

    public function test_admin_can_download_zip_backup_with_one_csv_per_table(): void
    {
        $admin = User::factory()->admin()->create([
            'password' => 'backup-test-password',
        ]);
        $student = User::factory()->student()->create(['name' => 'Backup Export Student']);
        Concern::factory()->for($student, 'author')->create([
            'subject' => 'Backup export sample concern',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.backup.download'))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/zip');

        $path = tempnam(sys_get_temp_dir(), 'scfms-test-backup-');
        $this->assertNotFalse($path);
        file_put_contents($path, $response->streamedContent());

        $zip = new ZipArchive;
        $this->assertTrue($zip->open($path));

        foreach ([
            'roles.csv',
            'users.csv',
            'categories.csv',
            'concerns.csv',
            'replies.csv',
            'attachments.csv',
            'ratings.csv',
            'audit_logs.csv',
            'notifications.csv',
            'ticket_sequences.csv',
            'README.txt',
        ] as $entry) {
            $this->assertNotFalse($zip->locateName($entry), "Missing {$entry} in backup archive.");
        }

        $usersCsv = (string) $zip->getFromName('users.csv');
        $concernsCsv = (string) $zip->getFromName('concerns.csv');

        $this->assertStringContainsString('Backup Export Student', $usersCsv);
        $this->assertStringContainsString('[redacted]', $usersCsv);
        $this->assertStringNotContainsString('$2y$', $usersCsv);
        $this->assertStringContainsString('Backup export sample concern', $concernsCsv);

        $zip->close();
        unlink($path);

        $this->assertDatabaseHas('audit_logs', [
            'action' => 'database_exported',
            'user_id' => $admin->id,
        ]);
    }

    public function test_non_admins_cannot_download_zip_backup(): void
    {
        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->get(route('admin.backup.download'))
            ->assertForbidden();
    }

    public function test_rating_follow_up_reminders_are_sent_once_after_three_days(): void
    {
        $student = User::factory()->student()->create();

        $eligible = Concern::factory()->for($student, 'author')->resolved()->create([
            'resolved_at' => now()->subDays(4),
        ]);
        Concern::factory()->for($student, 'author')->resolved()->create([
            'resolved_at' => now()->subDay(),
        ]);
        $rated = Concern::factory()->for($student, 'author')->resolved()->create([
            'resolved_at' => now()->subDays(5),
        ]);
        Rating::factory()->for($rated, 'concern')->for($student, 'user')->create();

        $this->artisan('app:send-rating-follow-up-reminders')->assertSuccessful();
        $this->artisan('app:send-rating-follow-up-reminders')->assertSuccessful();

        $this->assertDatabaseHas('notifications', [
            'user_id' => $student->id,
            'concern_id' => $eligible->id,
            'type' => 'rating_reminder',
        ]);
        $this->assertSame(1, Notification::where('type', 'rating_reminder')->count());
    }

    public function test_register_page_renders_password_strength_meter(): void
    {
        $this->get(route('register.show'))
            ->assertOk()
            ->assertSee('data-password-strength', false)
            ->assertSee(__('labels.password_strength'))
            ->assertSee(__('labels.strength_weak'));
    }
}
