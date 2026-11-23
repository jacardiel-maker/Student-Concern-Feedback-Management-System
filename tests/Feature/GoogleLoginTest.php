<?php

namespace Tests\Feature;

use App\Enums\StudentApprovalStatus;
use App\Http\Controllers\Auth\GoogleOnboardingController;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class GoogleLoginTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_login_page_offers_google_only_when_credentials_are_configured(): void
    {
        $this->configureGoogle();

        $this->get(route('login.show'))
            ->assertOk()
            ->assertSee(route('auth.google.redirect'))
            ->assertSee(__('labels.continue_with_google'));

        config()->set('services.google.client_id', null);

        $this->get(route('login.show'))
            ->assertOk()
            ->assertDontSee(__('labels.continue_with_google'));
    }

    public function test_redirect_route_sends_the_student_to_google(): void
    {
        $this->configureGoogle();
        Socialite::fake('google');

        $this->get(route('auth.google.redirect'))->assertRedirect();
    }

    public function test_redirect_route_reports_missing_configuration(): void
    {
        config()->set('services.google.client_id', null);
        config()->set('services.google.client_secret', null);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.google_not_configured'));
    }

    public function test_existing_student_signs_in_and_links_their_google_account_by_email(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        $student = User::factory()->student()->create(['email' => 'student@scfms.test']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Student Name',
            'email' => 'student@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', __('messages.logged_in'));

        $this->assertAuthenticatedAs($student);
        $this->assertDatabaseHas('users', ['id' => $student->id, 'google_id' => 'google-123']);
    }

    public function test_previously_linked_student_signs_in_by_google_id(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        $student = User::factory()->student()->create([
            'email' => 'student@scfms.test',
            'google_id' => 'google-123',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Student Name',
            'email' => 'different@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_google_account_linked_to_a_different_student_is_rejected(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        $student = User::factory()->student()->create([
            'email' => 'student@scfms.test',
            'google_id' => 'google-old',
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-new',
            'name' => 'Student Name',
            'email' => 'student@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.google_login_failed'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'google_id' => 'google-old']);
    }

    public function test_teachers_and_admins_cannot_use_google_sign_in(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        $teacher = User::factory()->teacher()->create(['email' => 'teacher@scfms.test']);
        $admin = User::factory()->admin()->create(['email' => 'admin@scfms.test']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Staff Member',
            'email' => 'teacher@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.google_students_only'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $teacher->id, 'google_id' => null]);
        $this->assertDatabaseHas('users', ['id' => $admin->id, 'google_id' => null]);
    }

    public function test_inactive_student_cannot_use_google_sign_in(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        $student = User::factory()->student()->create([
            'email' => 'student@scfms.test',
            'is_active' => false,
        ]);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Student Name',
            'email' => 'student@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.account_inactive'));

        $this->assertGuest();
        $this->assertDatabaseHas('users', ['id' => $student->id, 'google_id' => null]);
    }

    public function test_unknown_google_email_creates_a_pending_student_account(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'New Student',
            'email' => 'new.student@scfms.test',
        ]));

        $response = $this->get(route('auth.google.callback'))
            ->assertRedirect(route('auth.google.complete'));

        $this->assertGuest();

        $student = User::where('email', 'new.student@scfms.test')->firstOrFail();

        $this->assertSame('google-123', $student->google_id);
        $this->assertSame('New Student', $student->name);
        $this->assertFalse($student->is_active);
        $this->assertTrue($student->isAwaitingApproval());
        $this->assertNull($student->student_number);
        $response->assertSessionHas(GoogleOnboardingController::SESSION_KEY, $student->id);
    }

    public function test_student_submits_a_student_number_and_waits_for_approval(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'New Student',
            'email' => 'new.student@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('auth.google.complete'));

        $this->get(route('auth.google.complete'))
            ->assertOk()
            ->assertSee('New Student')
            ->assertSee('new.student@scfms.test')
            ->assertSee(__('labels.submit_for_approval'));

        $this->post(route('auth.google.complete.store'), [
            'student_number' => 'ST-2026-001',
            'department' => 'Computer Science',
        ])->assertRedirect(route('login.show'))
            ->assertSessionHas('success', __('messages.google_approval_submitted'));

        $student = User::where('email', 'new.student@scfms.test')->firstOrFail();

        $this->assertSame('ST-2026-001', $student->student_number);
        $this->assertSame('Computer Science', $student->department);
        $this->assertFalse($student->is_active);
        $this->assertTrue($student->isAwaitingApproval());
        $this->assertGuest();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('success', __('messages.google_awaiting_approval'));

        $this->assertGuest();
    }

    public function test_student_can_sign_in_after_admin_approval(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Approved Student',
            'email' => 'approved.student@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('auth.google.complete'));

        $student = User::where('email', 'approved.student@scfms.test')->firstOrFail();

        $this->post(route('auth.google.complete.store'), ['student_number' => 'ST-2026-002'])
            ->assertRedirect(route('login.show'));

        $student->forceFill([
            'approval_status' => StudentApprovalStatus::Approved,
            'is_active' => true,
        ])->save();

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('success', __('messages.logged_in'));

        $this->assertAuthenticatedAs($student);
    }

    public function test_student_number_must_be_unique_during_google_onboarding(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        User::factory()->student()->create(['student_number' => 'ST-2026-001']);

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'New Student',
            'email' => 'new.student@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))->assertRedirect(route('auth.google.complete'));

        $this->post(route('auth.google.complete.store'), ['student_number' => 'ST-2026-001'])
            ->assertSessionHasErrors('student_number');

        $student = User::where('email', 'new.student@scfms.test')->firstOrFail();

        $this->assertNull($student->student_number);
        $this->assertTrue($student->isAwaitingApproval());
    }

    public function test_google_onboarding_requires_a_valid_session(): void
    {
        $this->get(route('auth.google.complete'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.google_onboarding_expired'));

        $this->post(route('auth.google.complete.store'), ['student_number' => 'ST-2026-001'])
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.google_onboarding_expired'));
    }

    public function test_soft_deleted_google_account_cannot_sign_in_or_be_duplicated(): void
    {
        $this->seed(RoleSeeder::class);
        $this->configureGoogle();

        $student = User::factory()->student()->create([
            'email' => 'student@scfms.test',
            'google_id' => 'google-123',
        ]);
        $student->delete();

        Socialite::fake('google', SocialiteUser::fake([
            'id' => 'google-123',
            'name' => 'Student Name',
            'email' => 'student@scfms.test',
        ]));

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.account_inactive'));

        $this->assertGuest();
        $this->assertSame(1, User::withTrashed()->count());
    }

    public function test_provider_failure_is_handled_without_signing_in(): void
    {
        $this->configureGoogle();

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('setHttpClient')->once()->andReturnSelf();
        $provider->shouldReceive('user')->once()->andThrow(new RuntimeException('Invalid state'));
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login.show'))
            ->assertSessionHas('error', __('messages.google_login_failed'));

        $this->assertGuest();
    }

    private function configureGoogle(): void
    {
        config()->set('services.google.client_id', 'test-client-id');
        config()->set('services.google.client_secret', 'test-client-secret');
    }
}
