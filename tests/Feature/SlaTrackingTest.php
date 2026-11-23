<?php

namespace Tests\Feature;

use App\Enums\ConcernStatus;
use App\Models\Concern;
use App\Models\User;
use App\Services\ConcernService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class SlaTrackingTest extends TestCase
{
    use LazilyRefreshDatabase;

    protected function tearDown(): void
    {
        Carbon::setTestNow();

        parent::tearDown();
    }

    public function test_concern_is_flagged_after_five_business_days_without_a_status_change(): void
    {
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow('2026-11-23 12:00:00');

        $breached = Concern::factory()->create([
            'status_changed_at' => now()->subDays(10),
        ]);
        $fresh = Concern::factory()->create([
            'status_changed_at' => now()->subDays(2),
        ]);
        $resolved = Concern::factory()->create([
            'status' => ConcernStatus::Resolved,
            'status_changed_at' => now()->subDays(10),
        ]);

        $this->assertTrue($breached->isSlaBreached());
        $this->assertFalse($fresh->isSlaBreached());
        $this->assertFalse($resolved->isSlaBreached());

        $breachedIds = Concern::slaBreached()->pluck('id');

        $this->assertTrue($breachedIds->contains($breached->id));
        $this->assertFalse($breachedIds->contains($fresh->id));
        $this->assertFalse($breachedIds->contains($resolved->id));
    }

    public function test_status_change_resets_the_sla_clock_and_renders_a_badge(): void
    {
        $this->seed(RoleSeeder::class);
        Carbon::setTestNow('2026-11-23 12:00:00');

        $admin = User::factory()->admin()->create();
        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create([
            'status_changed_at' => now()->subDays(10),
        ]);

        $this->actingAs($student)->get(route('concerns.index'))
            ->assertOk()
            ->assertSee(__('labels.sla_breached'));

        app(ConcernService::class)->updateStatus($concern, ConcernStatus::UnderReview, $admin);

        $this->assertFalse($concern->fresh()->isSlaBreached());
        $this->assertTrue($concern->status_changed_at->equalTo(now()));
    }
}
