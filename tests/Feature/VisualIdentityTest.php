<?php

namespace Tests\Feature;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Models\Concern;
use App\Models\Notification;
use App\Models\Reply;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class VisualIdentityTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_renders_split_hero_with_animated_plane(): void
    {
        $this->get(route('login.show'))
            ->assertOk()
            ->assertSee(__('labels.login_hero_title'))
            ->assertSee('login-illustration')
            ->assertSee('login-plane')
            ->assertSee('login-flight-path');
    }

    public function test_my_concerns_marks_urgent_and_resolved_rows_differently(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        Concern::factory()->for($student, 'author')->create([
            'priority' => ConcernPriority::Urgent,
            'subject' => 'Urgent safety concern',
        ]);
        Concern::factory()->for($student, 'author')->resolved()->create([
            'priority' => ConcernPriority::Normal,
            'subject' => 'Settled concern',
        ]);

        $this->actingAs($student)->get(route('concerns.index'))
            ->assertOk()
            ->assertSee('bg-clay-50/70', false)
            ->assertSee('urgent-pulse')
            ->assertSee('bg-sage-50/70', false)
            ->assertSee(__('labels.workflow_position', ['status' => ConcernStatus::Resolved->label()]));
    }

    public function test_ticket_page_renders_the_five_stage_workflow_stepper(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id]);

        $response = $this->actingAs($teacher)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee('data-workflow-stepper', false)
            ->assertSee('data-advancing="false"', false);

        foreach (ConcernStatus::cases() as $status) {
            $response->assertSee($status->label());
        }
    }

    public function test_status_advance_flashes_the_stepper_animation_target(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->patch(route('concerns.status.update', $concern), [
            'status' => ConcernStatus::UnderReview->value,
        ])->assertRedirect()
            ->assertSessionHas('status_change', [
                'concern_id' => $concern->id,
                'from' => ConcernStatus::Pending->value,
                'to' => ConcernStatus::UnderReview->value,
            ]);

        $this->actingAs($teacher)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee('data-advancing="true"', false)
            ->assertSee('data-target-progress="25"', false)
            ->assertSee('status-settle');
    }

    public function test_new_reply_is_marked_for_fade_in_after_redirect(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $concern = Concern::factory()->create(['recipient_id' => $teacher->id]);

        $this->actingAs($teacher)->post(route('concerns.reply', $concern), [
            'body' => 'We are looking into this.',
        ])->assertRedirect();

        $reply = Reply::firstOrFail();

        $this->actingAs($teacher)->get(route('concerns.show', $concern))
            ->assertOk()
            ->assertSee('reply-enter')
            ->assertSee($reply->body);
    }

    public function test_notification_bell_swings_when_unread_notifications_exist(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();
        Notification::factory()->for($student)->for($concern)->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('bell-swing');
    }

    public function test_notification_bell_stays_still_when_notifications_are_read(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();
        Notification::factory()->for($student)->for($concern)->read()->create();

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('bell-swing');
    }

    public function test_concern_form_exposes_anonymous_hint_interaction_hooks(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();

        $this->actingAs($student)->get(route('concerns.create'))
            ->assertOk()
            ->assertSee('data-anonymous-panel', false)
            ->assertSee('data-anonymous-toggle', false)
            ->assertSee('data-anonymous-hint', false);
    }

    public function test_flash_messages_render_as_dismissible_toasts(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();

        $this->actingAs($student)
            ->withSession(['success' => __('messages.concern_created', ['ticket' => 'SC-2026-0001'])])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('data-toast', false)
            ->assertSee('toast-enter')
            ->assertSee('data-toast-dismiss', false);
    }
}
