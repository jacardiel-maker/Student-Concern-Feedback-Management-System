<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_user_can_update_email_opt_in_and_password(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->student()->create(['password' => 'password123']);

        $this->actingAs($user)->get(route('profile.edit'))
            ->assertOk()
            ->assertSee(__('labels.email_opt_in'))
            ->assertSee(__('labels.change_password'));

        $this->actingAs($user)->patch(route('profile.update'), [
            'email_opt_in' => 1,
            'current_password' => 'password123',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertSessionHas('success', __('messages.profile_updated'));

        $user->refresh();

        $this->assertTrue($user->email_opt_in);
        $this->assertTrue(Hash::check('new-password123', $user->password));
    }

    public function test_user_cannot_change_password_without_the_current_password(): void
    {
        $this->seed(RoleSeeder::class);
        $user = User::factory()->student()->create(['password' => 'password123']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'email_opt_in' => 1,
            'current_password' => 'wrong-password',
            'password' => 'new-password123',
            'password_confirmation' => 'new-password123',
        ])->assertSessionHasErrors('current_password');

        $user->refresh();

        $this->assertTrue(Hash::check('password123', $user->password));
    }
}
