@extends('layouts.app')

@section('pageTitle', __('labels.home'))

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="rounded-2xl border border-slate-200 bg-white p-8 shadow-sm sm:p-12">
            <p class="text-sm font-semibold uppercase tracking-wide text-spruce-600">SCFMS</p>
            <h1 class="mt-3 text-3xl font-bold text-slate-900 sm:text-4xl">
                {{ __('messages.welcome') }}
            </h1>
            <p class="mt-4 max-w-2xl text-base leading-relaxed text-slate-600">
                Submit concerns privately, follow their progress, and give feedback through a single, transparent workflow for students, teachers, and administrators.
            </p>

            <div class="mt-8 flex flex-wrap gap-3">
                @guest
                    <a href="{{ route('login.show') }}" class="inline-flex items-center rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                        {{ __('labels.login') }}
                    </a>
                    <a href="{{ route('register.show') }}" class="inline-flex items-center rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('labels.register') }}
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="inline-flex items-center rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                        {{ __('labels.dashboard') }}
                    </a>
                @endguest
            </div>
        </div>

        <div class="mt-6 grid gap-4 sm:grid-cols-3">
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">{{ __('labels.student_portal') }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ __('labels.submit_concern') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">{{ __('labels.teacher_portal') }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ __('labels.assigned_concerns') }}</p>
            </div>
            <div class="rounded-xl border border-slate-200 bg-white p-5 shadow-sm">
                <p class="text-sm font-semibold text-slate-900">{{ __('labels.admin_portal') }}</p>
                <p class="mt-2 text-sm text-slate-600">{{ __('labels.reports') }}</p>
            </div>
        </div>
    </div>
@endsection
