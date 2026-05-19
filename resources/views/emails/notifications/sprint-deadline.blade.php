@extends('emails.layout')

@section('content')
    <h1>Sprint deadline approaching</h1>
    <p>Hi {{ $user->name }},</p>
    <p>The sprint <strong>{{ $sprint->name }}</strong> on <strong>{{ $sprint->project?->name }}</strong> is ending in <strong>3 days</strong>.</p>
    <div class="meta">
        <p><strong>Deadline:</strong> {{ $sprint->deadline?->format('M d, Y') }}</p>
    </div>
    <a href="{{ route('projects.show', $sprint->project) }}" class="button">View Sprint</a>
@endsection
