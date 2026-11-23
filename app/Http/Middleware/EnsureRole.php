<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next, string $roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return $request->expectsJson()
                ? response()->json(['message' => __('messages.login_required')], Response::HTTP_UNAUTHORIZED)
                : redirect()->guest(route('login.show'));
        }

        if (! $user->is_active) {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login.show')->with('error', __('messages.account_inactive'));
        }

        $allowedRoles = array_map('trim', explode('|', $roles));

        foreach ($allowedRoles as $role) {
            if ($user->hasRole($role)) {
                return $next($request);
            }
        }

        abort(Response::HTTP_FORBIDDEN, __('messages.forbidden'));
    }
}
