@extends('layouts.app')

@section('pageTitle', __('labels.rate'))

@section('content')
    <div class="mx-auto max-w-2xl">
        <a href="{{ route('concerns.show', $concern) }}" class="text-sm font-medium text-spruce-700 hover:underline">{{ __('labels.back') }}</a>

        <div class="mt-3 rounded-xl border border-slate-200 bg-white p-6 shadow-sm">
            <h1 class="font-serif text-3xl font-semibold text-spruce-800">{{ __('labels.rate') }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $concern->ticket_no }} &mdash; {{ $concern->subject }}</p>

            <form method="POST" action="{{ route('concerns.rate', $concern) }}" class="mt-6 space-y-6">
                @csrf

                <fieldset>
                    <legend class="text-sm font-medium text-slate-700">{{ __('labels.score') }}</legend>
                    <div class="mt-3 flex flex-wrap gap-3">
                        @foreach (range(1, 5) as $score)
                            <div class="flex items-center gap-2 rounded-lg border border-slate-200 px-3 py-2">
                                <input id="score-{{ $score }}" name="score" type="radio" value="{{ $score }}" @checked(old('score') == $score) class="h-4 w-4 text-spruce-600 focus:ring-spruce-500">
                                <label for="score-{{ $score }}" class="text-sm text-slate-700">{{ $score }}</label>
                            </div>
                        @endforeach
                    </div>
                </fieldset>

                <div>
                    <label for="comment" class="block text-sm font-medium text-slate-700">{{ __('labels.comment') }}</label>
                    <textarea id="comment" name="comment" rows="5" class="mt-1.5 w-full rounded-lg border-slate-300 px-3 py-2.5 text-sm shadow-sm focus:border-spruce-500 focus:ring-spruce-500">{{ old('comment') }}</textarea>
                </div>

                <div class="flex flex-wrap gap-3">
                    <button type="submit" class="rounded-lg bg-spruce-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-spruce-700">
                        {{ __('labels.submit') }}
                    </button>
                    <a href="{{ route('concerns.show', $concern) }}" class="rounded-lg border border-slate-300 px-5 py-2.5 text-sm font-semibold text-slate-700 hover:bg-slate-50">
                        {{ __('labels.cancel') }}
                    </a>
                </div>
            </form>
        </div>
    </div>
@endsection
