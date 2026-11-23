<?php

namespace Tests\Feature;

use App\Models\Concern;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

class LocalizationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_switching_to_filipino_translates_the_concern_index(): void
    {
        $this->seed(RoleSeeder::class);

        $student = User::factory()->student()->create();
        Concern::factory()->for($student, 'author')->create(['subject' => 'Localization concern']);

        $this->actingAs($student)->post(route('language.switch'), ['locale' => 'fil'])
            ->assertRedirect();

        $this->assertSame('fil', session('locale'));
        $this->assertSame('fil', app()->getLocale());

        $this->actingAs($student)->get(route('concerns.index'))
            ->assertOk()
            ->assertSee('Mga Alalahanin Ko')
            ->assertSee('Nakabinbin')
            ->assertSee('Localization concern');
    }

    public function test_unsupported_locale_is_rejected(): void
    {
        $this->post(route('language.switch'), ['locale' => 'de'])
            ->assertSessionHas('error', __('messages.unsupported_locale'));

        $this->assertNull(session('locale'));
        $this->assertSame('en', app()->getLocale());
    }
}
