<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function show(): View
    {
        return view('auth.register');
    }

    public function store(RegisterRequest $request): RedirectResponse
    {
        $studentRole = Role::where('name', 'student')->firstOrFail();

        $user = User::create([
            'role_id' => $studentRole->id,
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'student_number' => $request->validated('student_number'),
            'department' => $request->validated('department'),
            'password' => $request->validated('password'),
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()->route('dashboard')->with('success', __('messages.registered'));
    }
}
