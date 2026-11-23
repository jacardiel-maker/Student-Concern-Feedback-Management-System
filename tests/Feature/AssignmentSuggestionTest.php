<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Concern;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class AssignmentSuggestionTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_assignment_page_suggests_the_teacher_with_the_most_cases_per_category(): void
    {
        $this->seed(RoleSeeder::class);

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Academic']);
        $busyTeacher = User::factory()->teacher()->create(['name' => 'Busy Teacher']);
        $otherTeacher = User::factory()->teacher()->create(['name' => 'Other Teacher']);

        Concern::factory()->count(2)->for($category)->create(['recipient_id' => $busyTeacher->id]);
        Concern::factory()->for($category)->create(['recipient_id' => $otherTeacher->id]);

        $response = $this->actingAs($admin)->get(route('admin.assignments.index'));

        $response->assertOk()
            ->assertSee(__('labels.assignment_suggestions'))
            ->assertSee('Busy Teacher')
            ->assertSee('2');

        $response->assertViewHas('suggestions', function ($suggestions) use ($category, $busyTeacher): bool {
            $suggestion = $suggestions->get($category->id);

            return $suggestion !== null
                && $suggestion->recipient_id === $busyTeacher->id
                && (int) $suggestion->handled_count === 2;
        });
    }
}
