<?php

namespace App\Http\Controllers\Auth;

use App\Enums\StudentApprovalStatus;
use App\Http\Controllers\Controller;
use App\Models\Role;
use App\Models\User;
use App\Services\GoogleHttpClientFactory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Laravel\Socialite\Contracts\Provider as SocialiteProvider;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class GoogleLoginController extends Controller
{
    public function __construct(private GoogleHttpClientFactory $googleHttpClientFactory) {}

    public function redirect(): RedirectResponse
    {
        if (! $this->isConfigured()) {
            return redirect()->route('login.show')->with('error', __('messages.google_not_configured'));
        }

        return $this->googleDriver()->redirect();
    }

    public function callback(Request $request): RedirectResponse
    {
        try {
            $googleUser = $this->googleDriver()->user();
        } catch (Throwable $exception) {
            Log::warning('Google sign-in callback failed.', ['reason' => $exception->getMessage()]);

            return redirect()->route('login.show')->with('error', __('messages.google_login_failed'));
        }

        $googleId = $googleUser->getId();
        $email = $googleUser->getEmail();

        if (! is_string($googleId) || $googleId === '' || ! is_string($email) || $email === '') {
            return redirect()->route('login.show')->with('error', __('messages.google_login_failed'));
        }

        $user = User::withTrashed()->where('google_id', $googleId)->first()
            ?? User::withTrashed()->where('email', $email)->first();

        if ($user !== null && $user->trashed()) {
            return redirect()->route('login.show')->with('error', __('messages.account_inactive'));
        }

        if ($user === null) {
            return $this->beginApproval($request, $googleId, $email, $googleUser->getName());
        }

        if (! $user->isStudent()) {
            return redirect()->route('login.show')->with('error', __('messages.google_students_only'));
        }

        if ($user->approval_status === StudentApprovalStatus::Pending) {
            $request->session()->put(GoogleOnboardingController::SESSION_KEY, $user->id);

            if (blank($user->student_number)) {
                return redirect()->route('auth.google.complete');
            }

            return redirect()->route('login.show')->with('success', __('messages.google_awaiting_approval'));
        }

        if (! $user->is_active) {
            return redirect()->route('login.show')->with('error', __('messages.account_inactive'));
        }

        if ($user->google_id !== null && $user->google_id !== $googleId) {
            return redirect()->route('login.show')->with('error', __('messages.google_login_failed'));
        }

        if ($user->google_id === null) {
            $user->forceFill(['google_id' => $googleId])->save();
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', __('messages.logged_in'));
    }

    private function beginApproval(Request $request, string $googleId, string $email, mixed $name): RedirectResponse
    {
        $studentRole = Role::where('name', 'student')->first();

        if ($studentRole === null) {
            return redirect()->route('login.show')->with('error', __('messages.google_login_failed'));
        }

        $displayName = is_string($name) ? trim($name) : '';

        $user = User::create([
            'role_id' => $studentRole->id,
            'name' => $displayName !== '' ? $displayName : Str::before($email, '@'),
            'email' => $email,
            'google_id' => $googleId,
            'password' => Str::password(32),
            'is_active' => false,
            'approval_status' => StudentApprovalStatus::Pending,
        ]);

        $request->session()->put(GoogleOnboardingController::SESSION_KEY, $user->id);

        return redirect()->route('auth.google.complete');
    }

    private function googleDriver(): SocialiteProvider
    {
        $driver = Socialite::driver('google');
        $driver->setHttpClient($this->googleHttpClientFactory->make());

        return $driver;
    }

    private function isConfigured(): bool
    {
        return filled(config('services.google.client_id')) && filled(config('services.google.client_secret'));
    }
}
