@extends('layouts.auth')

@section('pageTitle', __('labels.login'))

@section('authArt')
    <div class="my-auto w-full py-10">
        <p class="text-xs font-semibold uppercase tracking-[0.22em] text-sage-200">{{ __('labels.welcome_back') }}</p>
        <h2 class="mt-3 max-w-md font-serif text-4xl font-semibold leading-tight text-white">
            {{ __('labels.login_hero_title') }}
        </h2>
        <p class="mt-4 max-w-md text-sm leading-relaxed text-sage-100">
            {{ __('labels.login_hero_copy') }}
        </p>

        <div class="mt-8 rounded-2xl border border-white/10 bg-white/5 p-4">
            @include('partials.login_plane')
        </div>
    </div>
@endsection

@section('content')
    <div class="border-l-4 border-spruce-500 bg-white/80 px-6 py-7 shadow-sm sm:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sage-600">{{ config('app.name') }}</p>
        <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.login') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('labels.login_subtitle') }}</p>

        <form method="POST" action="{{ route('login.store') }}" class="mt-7 space-y-5">
            @csrf

            <div>
                <label for="email" class="block text-sm font-medium text-slate-700">{{ __('labels.email') }}</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>

            <div>
                <label for="password" class="block text-sm font-medium text-slate-700">{{ __('labels.password') }}</label>
                <input id="password" name="password" type="password" required autocomplete="current-password" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>

            <div class="flex items-center">
                <input id="remember" name="remember" type="checkbox" value="1" @checked(old('remember')) class="h-4 w-4 rounded border-slate-300 text-spruce-600 focus:ring-spruce-500">
                <label for="remember" class="ml-2 text-sm text-slate-700">{{ __('labels.remember_me') }}</label>
            </div>

            <button type="submit" class="w-full rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                {{ __('labels.login') }}
            </button>
        </form>

        @if (config('services.google.client_id') && config('services.google.client_secret'))
            <div class="mt-5 flex items-center gap-3">
                <span class="h-px flex-1 bg-spruce-100"></span>
                <span class="text-xs uppercase tracking-widest text-slate-400">{{ __('labels.or') }}</span>
                <span class="h-px flex-1 bg-spruce-100"></span>
            </div>

            <a href="{{ route('auth.google.redirect') }}" class="mt-3 flex w-full items-center justify-center gap-2 rounded-lg border border-spruce-100 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 hover:bg-sage-50">
                <svg class="h-4 w-4" viewBox="0 0 24 24" aria-hidden="true">
                    <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92a5.06 5.06 0 0 1-2.2 3.32v2.77h3.57c2.08-1.92 3.27-4.74 3.27-8.1Z"/>
                    <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84A11 11 0 0 0 12 23Z"/>
                    <path fill="#FBBC05" d="M5.84 14.1a6.6 6.6 0 0 1 0-4.2V7.06H2.18a11 11 0 0 0 0 9.88l3.66-2.84Z"/>
                    <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.06l3.66 2.84C6.71 7.31 9.14 5.38 12 5.38Z"/>
                </svg>
                {{ __('labels.continue_with_google') }}
            </a>
        @endif

        <p class="mt-6 text-center text-sm text-slate-600">
            {{ __('labels.no_account') }}
            <a href="{{ route('register.show') }}" class="font-medium text-spruce-700 hover:underline">{{ __('labels.register') }}</a>
        </p>
    </div>
@endsection
