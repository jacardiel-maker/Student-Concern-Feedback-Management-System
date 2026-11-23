<?php

namespace Tests\Feature;

use App\Enums\ConcernPriority;
use App\Models\Category;
use App\Models\Concern;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class UnmaskControllerTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_admin_unmasking_reveals_the_author_and_writes_an_audit_record(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $author = User::factory()->student()->create(['name' => 'Anonymous Student']);
        $category = Category::factory()->create(['is_urgent' => true, 'is_active' => true]);
        $concern = Concern::factory()->for($author, 'author')->for($category, 'category')->anonymous()->create([
            'subject' => 'Anonymous unmask concern',
            'priority' => ConcernPriority::Urgent,
        ]);
        $reason = 'Requested by the guidance counselor.';

        $this->actingAs($admin)->get(route('admin.unmask.show', $concern))
            ->assertOk()
            ->assertDontSee('Anonymous Student');

        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), ['reason' => $reason])
            ->assertOk()
            ->assertSee('Anonymous Student');

        $this->assertDatabaseHas('audit_logs', [
            'concern_id' => $concern->id,
            'user_id' => $admin->id,
            'action' => 'identity_unmasked',
            'old_value' => 'anonymous',
            'new_value' => 'Anonymous Student',
            'reason' => $reason,
        ]);
    }

    public function test_unmasking_requires_a_reason(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['is_urgent' => true, 'is_active' => true]);
        $concern = Concern::factory()->for($category, 'category')->anonymous()->create([
            'priority' => ConcernPriority::Urgent,
        ]);

        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), [
            'reason' => 'bad',
        ])->assertSessionHasErrors('reason');

        $this->assertDatabaseCount('audit_logs', 0);
    }

    public function test_non_anonymous_concern_cannot_be_unmasked(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()->create();

        $this->actingAs($admin)->get(route('admin.unmask.show', $concern))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.unmask.store', $concern), [
            'reason' => 'This concern is not anonymous.',
        ])->assertNotFound();
    }
}
