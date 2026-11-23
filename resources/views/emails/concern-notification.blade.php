<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <title>{{ $notificationTitle }}</title>
</head>
<body style="font-family: sans-serif; color: #1f2937;">
    <h1 style="font-size: 18px;">{{ $notificationTitle }}</h1>
    <p>{{ $notificationBody }}</p>
    @if ($concern)
        <p><strong>{{ __('labels.ticket_no') }}:</strong> {{ $concern->ticket_no }}</p>
        <p><strong>{{ __('labels.subject') }}:</strong> {{ $concern->subject }}</p>
    @endif
    <hr>
    <p style="font-size: 12px; color: #6b7280;">{{ config('app.name') }}</p>
</body>
</html>
