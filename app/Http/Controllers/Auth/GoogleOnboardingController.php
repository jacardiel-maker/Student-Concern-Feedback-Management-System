<?php

namespace App\Http\Controllers\Auth;

use App\Enums\StudentApprovalStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\CompleteGoogleRegistrationRequest;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class GoogleOnboardingController extends Controller
{
    public const SESSION_KEY = 'google_onboarding_user_id';

    public function show(): View|RedirectResponse
    {
        $user = $this->pendingUser();

        if ($user === null) {
            return redirect()->route('login.show')->with('error', __('messages.google_onboarding_expired'));
        }

        return view('auth.google-complete', [
            'studentName' => $user->name,
            'studentEmail' => $user->email,
        ]);
    }

    public function store(CompleteGoogleRegistrationRequest $request): RedirectResponse
    {
        $user = $this->pendingUser();

        if ($user === null) {
            return redirect()->route('login.show')->with('error', __('messages.google_onboarding_expired'));
        }

        $user->forceFill([
            'student_number' => $request->validated('student_number'),
            'department' => $request->validated('department'),
        ])->save();

        $request->session()->forget(self::SESSION_KEY);

        return redirect()->route('login.show')->with('success', __('messages.google_approval_submitted'));
    }

    private function pendingUser(): ?User
    {
        $userId = session(self::SESSION_KEY);

        if (! is_numeric($userId)) {
            return null;
        }

        $user = User::withTrashed()->find((int) $userId);

        if (! $user?->isStudent()
            || $user->trashed()
            || $user->approval_status !== StudentApprovalStatus::Pending
            || $user->google_id === null) {
            return null;
        }

        return $user;
    }
}
