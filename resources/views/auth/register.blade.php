@extends('layouts.auth')

@section('pageTitle', __('labels.register'))

@section('content')
    <div class="mx-auto max-w-2xl">
        <div class="border-l-4 border-spruce-500 bg-white/80 px-6 py-7 shadow-sm sm:px-8">
            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sage-600">{{ config('app.name') }}</p>
            <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.register') }}</h1>
            <p class="mt-2 text-sm text-slate-500">{{ __('labels.register_subtitle') }}</p>

            <form method="POST" action="{{ route('register.store') }}" class="mt-6 grid gap-5 sm:grid-cols-2">
                @csrf

                <div class="sm:col-span-2">
                    <label for="name" class="block text-sm font-medium text-slate-700">{{ __('labels.name') }}</label>
                    <input id="name" name="name" type="text" value="{{ old('name') }}" required autocomplete="name" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div>
                    <label for="email" class="block text-sm font-medium text-slate-700">{{ __('labels.email') }}</label>
                    <input id="email" name="email" type="email" value="{{ old('email') }}" required autocomplete="email" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div>
                    <label for="student_number" class="block text-sm font-medium text-slate-700">{{ __('labels.student_number') }}</label>
                    <input id="student_number" name="student_number" type="text" value="{{ old('student_number') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div class="sm:col-span-2">
                    <label for="department" class="block text-sm font-medium text-slate-700">{{ __('labels.department') }}</label>
                    <input id="department" name="department" type="text" value="{{ old('department') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div>
                    <label for="password" class="block text-sm font-medium text-slate-700">{{ __('labels.password') }}</label>
                    <input id="password" name="password" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    <div data-password-strength
                         data-label-empty="{{ __('labels.strength_empty') }}"
                         data-label-weak="{{ __('labels.strength_weak') }}"
                         data-label-fair="{{ __('labels.strength_fair') }}"
                         data-label-good="{{ __('labels.strength_good') }}"
                         data-label-strong="{{ __('labels.strength_strong') }}"
                         class="mt-3">
                        <div class="flex items-center justify-between gap-2 text-xs">
                            <span class="font-medium text-slate-600">{{ __('labels.password_strength') }}</span>
                            <span data-password-strength-label aria-live="polite" class="font-semibold text-slate-500">{{ __('labels.strength_empty') }}</span>
                        </div>
                        <div class="mt-2 flex gap-1.5" aria-hidden="true">
                            <span data-strength-bar class="h-1.5 flex-1 rounded-full bg-slate-300"></span>
                            <span data-strength-bar class="h-1.5 flex-1 rounded-full bg-slate-300"></span>
                            <span data-strength-bar class="h-1.5 flex-1 rounded-full bg-slate-300"></span>
                            <span data-strength-bar class="h-1.5 flex-1 rounded-full bg-slate-300"></span>
                        </div>
                        <p class="mt-2 text-[11px] text-slate-500">{{ __('labels.password_strength_hint') }}</p>
                    </div>
                </div>

                <div>
                    <label for="password_confirmation" class="block text-sm font-medium text-slate-700">{{ __('labels.confirm_password') }}</label>
                    <input id="password_confirmation" name="password_confirmation" type="password" required autocomplete="new-password" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                </div>

                <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
                    <button type="submit" class="rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                        {{ __('labels.register') }}
                    </button>
                    <a href="{{ route('login.show') }}" class="text-sm font-medium text-spruce-700 hover:underline">
                        {{ __('labels.login') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
