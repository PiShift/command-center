@php
    $lines = explode("\n", trim($description ?? ''));
    $firstNonEmpty = true;
@endphp
@foreach($lines as $line)
    @php $trimmed = rtrim($line); @endphp
    @if($trimmed === '')
        <div style="height:4px;"></div>
    @elseif($firstNonEmpty)
        @php $firstNonEmpty = false; @endphp
        <div style="font-weight:bold;font-size:11px;color:#1a1a1a;margin-bottom:4px;">{{ $trimmed }}</div>
    @elseif(str_starts_with($trimmed, '·') || str_starts_with($trimmed, '-'))
        <div style="font-size:9.5px;color:#666666;padding-left:10px;margin-bottom:2px;">· {{ trim(mb_substr($trimmed, 1)) }}</div>
    @else
        <div style="font-size:9.5px;color:#666666;margin-bottom:2px;">{{ $trimmed }}</div>
    @endif
@endforeach
