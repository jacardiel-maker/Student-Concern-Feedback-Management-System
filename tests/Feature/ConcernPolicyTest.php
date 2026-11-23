<?php

namespace Tests\Feature;

use App\Models\Concern;
use App\Models\Rating;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class ConcernPolicyTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_view_and_participate_permissions_follow_conversation_membership(): void
    {
        $this->seed(RoleSeeder::class);

        $author = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()->for($author, 'author')->create(['recipient_id' => $teacher->id]);

        $this->assertTrue(Gate::forUser($author)->allows('view', $concern));
        $this->assertTrue(Gate::forUser($teacher)->allows('view', $concern));
        $this->assertTrue(Gate::forUser($admin)->allows('view', $concern));
        $this->assertFalse(Gate::forUser($otherStudent)->allows('view', $concern));
        $this->assertFalse(Gate::forUser($otherTeacher)->allows('view', $concern));

        $this->assertTrue(Gate::forUser($author)->allows('participate', $concern));

        $closed = Concern::factory()->for($author, 'author')->closed()->create(['recipient_id' => $teacher->id]);

        $this->assertFalse(Gate::forUser($author)->allows('participate', $closed));
        $this->assertFalse(Gate::forUser($teacher)->allows('participate', $closed));
    }

    public function test_manage_is_limited_to_staff_participants(): void
    {
        $this->seed(RoleSeeder::class);

        $author = User::factory()->student()->create();
        $teacher = User::factory()->teacher()->create();
        $otherTeacher = User::factory()->teacher()->create();
        $admin = User::factory()->admin()->create();
        $concern = Concern::factory()->for($author, 'author')->create(['recipient_id' => $teacher->id]);

        $this->assertTrue(Gate::forUser($teacher)->allows('manage', $concern));
        $this->assertTrue(Gate::forUser($admin)->allows('manage', $concern));
        $this->assertFalse(Gate::forUser($author)->allows('manage', $concern));
        $this->assertFalse(Gate::forUser($otherTeacher)->allows('manage', $concern));
    }

    public function test_rate_requires_author_resolved_status_and_no_existing_rating(): void
    {
        $this->seed(RoleSeeder::class);

        $author = User::factory()->student()->create();
        $otherStudent = User::factory()->student()->create();
        $resolved = Concern::factory()->for($author, 'author')->resolved()->create();
        $pending = Concern::factory()->for($author, 'author')->create();
        $rated = Concern::factory()->for($author, 'author')->resolved()->create();

        Rating::factory()->for($rated)->for($author)->create(['score' => 4]);

        $this->assertTrue(Gate::forUser($author)->allows('rate', $resolved));
        $this->assertFalse(Gate::forUser($otherStudent)->allows('rate', $resolved));
        $this->assertFalse(Gate::forUser($author)->allows('rate', $pending));
        $this->assertFalse(Gate::forUser($author)->allows('rate', $rated));
    }
}
