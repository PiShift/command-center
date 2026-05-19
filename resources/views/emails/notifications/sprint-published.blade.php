@extends('emails.layout')

@section('content')
    <h1>Sprint published: {{ $sprint->name }}</h1>
    <p>Hi {{ $user->name }},</p>
    <p>A new sprint has been published on <strong>{{ $sprint->project?->name }}</strong>. You have <strong>{{ $taskCount }} tasks</strong> available in this sprint.</p>
    <div class="meta">
        @if($sprint->deadline)
            <p><strong>Deadline:</strong> {{ $sprint->deadline->format('M d, Y') }}</p>
        @endif
    </div>
    <a href="{{ route('projects.show', $sprint->project) }}" class="button">View Sprint</a>
@endsection
