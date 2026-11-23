<?php

namespace Tests\Feature;

use App\Enums\ConcernPriority;
use App\Enums\ConcernStatus;
use App\Models\Concern;
use App\Models\Notification;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_student_dashboard_renders_their_own_concern_stats(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();

        Concern::factory()->for($student, 'author')->create(['subject' => 'Student dashboard concern']);
        Concern::factory()->for($otherStudent, 'author')->create(['subject' => 'Hidden dashboard concern']);

        $this->actingAs($student)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Student dashboard concern')
            ->assertDontSee('Hidden dashboard concern');
    }

    public function test_teacher_dashboard_shows_urgent_queue_and_awaiting_first_response(): void
    {
        $this->seed(RoleSeeder::class);

        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();

        Concern::factory()->create([
            'recipient_id' => $teacher->id,
            'subject' => 'Urgent assigned to me',
            'status' => ConcernStatus::Pending,
            'priority' => ConcernPriority::Urgent,
        ]);
        Concern::factory()->create([
            'recipient_id' => $teacher->id,
            'subject' => 'Needs first response',
            'status' => ConcernStatus::Pending,
            'priority' => ConcernPriority::Normal,
        ]);
        Concern::factory()->create([
            'recipient_id' => $otherTeacher->id,
            'subject' => 'Assigned to someone else',
        ]);

        $this->actingAs($teacher)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Urgent assigned to me')
            ->assertSee('Needs first response')
            ->assertDontSee('Assigned to someone else');
    }

    public function test_admin_dashboard_renders_all_concerns(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        Concern::factory()->count(2)->create();

        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_user_can_mark_only_their_own_notification_as_read(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();
        $notification = Notification::factory()->for($student)->for($concern)->create();
        $otherNotification = Notification::factory()->for($otherStudent)->create();

        $this->actingAs($student)->patch(route('notifications.read', $notification))
            ->assertRedirectToRoute('concerns.show', $concern);

        $this->assertTrue($notification->fresh()->is_read);
        $this->assertFalse($otherNotification->fresh()->is_read);

        $this->actingAs($student)->patch(route('notifications.read', $otherNotification))->assertForbidden();
    }

    public function test_notification_center_lists_and_marks_all_read(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        Notification::factory()->for($student)->count(2)->create();
        Notification::factory()->for(User::factory()->student()->create())->create(['title' => 'Other user notification']);

        $this->actingAs($student)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee(__('labels.mark_all_as_read'))
            ->assertDontSee('Other user notification');

        $this->actingAs($student)->post(route('notifications.readAll'))->assertRedirect();

        $this->assertSame(0, $student->notifications()->unread()->count());
    }
}
