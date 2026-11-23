<?php

namespace Tests\Feature;

use App\Models\Concern;
use App\Models\User;
use Database\Seeders\CategorySeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class TicketPrintTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_printable_ticket_contains_a_qr_code_linking_back_to_the_concern(): void
    {
        $this->seed([RoleSeeder::class, CategorySeeder::class]);

        $student = User::factory()->student()->create();
        $concern = Concern::factory()->for($student, 'author')->create();

        $this->actingAs($student)->get(route('concerns.print', $concern))
            ->assertOk()
            ->assertSee($concern->ticket_no)
            ->assertSee(route('concerns.show', $concern))
            ->assertSee('<svg', false);
    }
}
