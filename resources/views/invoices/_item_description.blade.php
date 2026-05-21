@php
    $lines = explode("\n", trim($description ?? ''));
    $firstNonEmpty = true;
@endphp
@foreach($lines as $line)
    @php $trimmed = rtrim($line); @endphp
    @if($trimmed === '')
        <br>
    @elseif($firstNonEmpty)
        @php $firstNonEmpty = false; @endphp
        <strong style="font-weight:600">{{ $trimmed }}</strong>
    @elseif(str_starts_with($trimmed, '·') || str_starts_with($trimmed, '-'))
        <div style="padding-left:8px">{{ $trimmed }}</div>
    @else
        <div>{{ $trimmed }}</div>
    @endif
@endforeach
