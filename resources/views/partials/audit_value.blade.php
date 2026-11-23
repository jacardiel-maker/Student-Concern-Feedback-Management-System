@php
    if ($value === null) {
        $display = '—';
    } elseif (is_bool($value)) {
        $display = $value ? 'true' : 'false';
    } elseif (is_array($value) || is_object($value)) {
        $display = json_encode($value);
    } else {
        $display = (string) $value;
    }
@endphp

<span class="break-words text-slate-700">{{ $display }}</span>
