@extends('layouts.app')

@section('pageTitle', __('labels.profile'))

@section('content')
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.profile') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ __('labels.profile_help') }}</p>
        </div>

        <section class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            <dl class="grid gap-4 sm:grid-cols-2">
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.name') }}</dt>
                    <dd class="mt-1 text-sm font-medium text-slate-900">{{ $user->name }}</dd>
                </div>
                <div>
                    <dt class="text-xs font-medium uppercase tracking-wide text-slate-500">{{ __('labels.email') }}</dt>
                    <dd class="mt-1 text-sm text-slate-700">{{ $user->email }}</dd>
                </div>
            </dl>
        </section>

        <form method="POST" action="{{ route('profile.update') }}" class="space-y-6 rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
            @csrf
            @method('PATCH')

            <fieldset class="space-y-4">
                <legend class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.notifications') }}</legend>
                <label class="flex items-center gap-3 rounded-lg border border-slate-200 p-3">
                    <input type="checkbox" name="email_opt_in" value="1" @checked(old('email_opt_in', $user->email_opt_in)) class="h-4 w-4 rounded border-slate-300 text-spruce-600 focus:ring-spruce-500">
                    <span>
                        <span class="block text-sm font-medium text-slate-900">{{ __('labels.email_opt_in') }}</span>
                        <span class="block text-xs text-slate-500">{{ __('labels.email_opt_in_help') }}</span>
                    </span>
                </label>
            </fieldset>

            <fieldset class="space-y-4 border-t border-slate-200 pt-5">
                <legend class="font-serif text-lg font-semibold text-spruce-800">{{ __('labels.change_password') }}</legend>
                <p class="text-xs text-slate-500">{{ __('labels.change_password_help') }}</p>
                <div>
                    <label for="current_password" class="block text-xs font-medium text-slate-600">{{ __('labels.current_password') }}</label>
                    <input id="current_password" name="current_password" type="password" autocomplete="current-password" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                </div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div>
                        <label for="password" class="block text-xs font-medium text-slate-600">{{ __('labels.new_password') }}</label>
                        <input id="password" name="password" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    </div>
                    <div>
                        <label for="password_confirmation" class="block text-xs font-medium text-slate-600">{{ __('labels.confirm_password') }}</label>
                        <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password" class="mt-1.5 w-full rounded-lg border-slate-300 py-2 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
                    </div>
                </div>
            </fieldset>

            <div class="flex justify-end">
                <button type="submit" class="rounded-lg bg-spruce-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                    {{ __('labels.save') }}
                </button>
            </div>
        </form>
    </div>
@endsection
