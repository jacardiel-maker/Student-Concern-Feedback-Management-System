<?php

namespace App\Providers;

use App\Models\Concern;
use App\Models\User;
use App\Policies\ConcernPolicy;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Str;

class AppServiceProvider extends ServiceProvider
{
    /** @var array<class-string, class-string> */
    protected $policies = [
        Concern::class => ConcernPolicy::class,
    ];

    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        foreach ($this->policies as $model => $policy) {
            Gate::policy($model, $policy);
        }

        RateLimiter::for('login', function (Request $request): Limit {
            $identifier = Str::transliterate(
                Str::lower($request->string('email')->toString()).'|'.$request->ip(),
            );

            return Limit::perMinute(5)->by($identifier);
        });

        View::composer('layouts.app', function (ViewContract $view): void {
            /** @var User|null $user */
            $user = auth()->user();

            $view->with('unreadNotificationCount', $user
                ? $user->notifications()->unread()->count()
                : 0);
        });
    }
}
