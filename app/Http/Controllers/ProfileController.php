<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function edit(): View
    {
        return view('profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = $request->user();

        $user->email_opt_in = $request->boolean('email_opt_in');

        if ($request->filled('password')) {
            $user->password = $request->input('password');
        }

        $user->save();

        return back()->with('success', __('messages.profile_updated'));
    }
}
