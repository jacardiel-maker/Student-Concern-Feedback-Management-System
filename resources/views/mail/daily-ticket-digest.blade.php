<x-mail::message>
# {{ __('messages.digest_subject') }}

{{ __('messages.digest_greeting', ['name' => $recipient->name]) }}

@if ($concerns->isNotEmpty())
{{ __('messages.digest_summary', ['count' => $concerns->count()]) }}

@foreach ($concerns as $concern)
- **{{ $concern->ticket_no }}** — {{ $concern->subject }} ({{ $concern->status->label() }})
@endforeach

<x-mail::button :url="route('teacher.assigned.index')">
{{ __('labels.assigned_concerns') }}
</x-mail::button>
@else
{{ __('messages.digest_empty') }}
@endif

{{ config('app.name') }}
</x-mail::message>
