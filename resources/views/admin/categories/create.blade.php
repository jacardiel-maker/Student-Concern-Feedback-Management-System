@extends('layouts.app')

@section('pageTitle', __('labels.create') . ' ' . __('labels.categories'))

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('admin.categories.index') }}" class="text-sm font-medium text-spruce-700 hover:underline">{{ __('labels.back') }}</a>
        <h1 class="mt-2 font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.create') }} {{ __('labels.categories') }}</h1>

        <form method="POST" action="{{ route('admin.categories.store') }}" class="mt-6 space-y-5 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            @csrf

            <div>
                <label for="name" class="block text-sm font-medium text-slate-700">{{ __('labels.name') }}</label>
                <input id="name" name="name" type="text" value="{{ old('name') }}" required class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>

            <div>
                <label for="name_fil" class="block text-sm font-medium text-slate-700">{{ __('labels.name_fil') }}</label>
                <input id="name_fil" name="name_fil" type="text" value="{{ old('name_fil') }}" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">
            </div>

            <div class="flex flex-wrap gap-6">
                <div class="flex items-center gap-2">
                    <input id="is_urgent" name="is_urgent" type="checkbox" value="1" @checked(old('is_urgent')) class="h-4 w-4 rounded border-slate-300 text-spruce-600 focus:ring-spruce-500">
                    <label for="is_urgent" class="text-sm text-slate-700">{{ __('labels.priority_urgent') }}</label>
                </div>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="is_active" value="0">
                    <input id="is_active" name="is_active" type="checkbox" value="1" @checked(old('is_active', true)) class="h-4 w-4 rounded border-slate-300 text-spruce-600 focus:ring-spruce-500">
                    <label for="is_active" class="text-sm text-slate-700">{{ __('labels.active') }}</label>
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <button type="submit" class="rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">{{ __('labels.create') }}</button>
                <a href="{{ route('admin.categories.index') }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">{{ __('labels.cancel') }}</a>
            </div>
        </form>
    </div>
@endsection
