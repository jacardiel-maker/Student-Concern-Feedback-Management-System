@php
    $colspan = $colspan ?? null;
@endphp

@if ($colspan !== null)
    <tr>
        <td colspan="{{ $colspan }}">@include('partials.empty_state_art')</td>
    </tr>
@else
    @include('partials.empty_state_art')
@endif
