@extends('layouts.auth')

@section('pageTitle', __('labels.complete_google_signup'))

@section('content')
    <div class="border-l-4 border-spruce-500 bg-white/80 px-6 py-7 shadow-sm sm:px-8">
        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-sage-600">{{ config('app.name') }}</p>
        <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.complete_google_signup') }}</h1>
        <p class="mt-2 text-sm text-slate-500">{{ __('labels.google_complete_subtitle') }}</p>

        <div class="mt-5 rounded-xl border border-sage-100 bg-sage-50/70 px-4 py-3">
            <p class="text-xs font-semibold uppercase tracking-widest text-sage-600">{{ __('labels.google_account') }}</p>
            <p class="mt-1 text-sm font-semibold text-slate-800">{{ $studentName }}</p>
            <p class="text-sm text-slate-500">{{ $studentEmail }}</p>
        </div>

        <form method="POST" action="{{ route('auth.google.complete.store') }}" class="mt-6 grid gap-5 sm:grid-cols-2">
            @csrf

            <div>
                <label for="student_number" class="block text-sm font-medium text-slate-700">{{ __('labels.student_number') }}</label>
                <input id="student_number" name="student_number" type="text" value="{{ old('student_number') }}" required autocomplete="off" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>

            <div>
                <label for="department" class="block text-sm font-medium text-slate-700">{{ __('labels.department') }}</label>
                <input id="department" name="department" type="text" value="{{ old('department') }}" autocomplete="organization" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>

            <p class="sm:col-span-2 rounded-lg border border-amber-100 bg-amber-50 px-4 py-3 text-sm text-amber-900">
                {{ __('labels.google_approval_notice') }}
            </p>

            <div class="sm:col-span-2 flex flex-wrap items-center justify-between gap-3">
                <button type="submit" class="rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                    {{ __('labels.submit_for_approval') }}
                </button>
                <a href="{{ route('login.show') }}" class="text-sm font-medium text-slate-600 hover:underline">{{ __('labels.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
