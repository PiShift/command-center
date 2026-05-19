@extends('emails.layout')

@section('content')
    <h1>Project health alert: {{ $project->name }}</h1>
    <p>Hi {{ $user->name }},</p>
    <p>The health of project <strong>{{ $project->name }}</strong> has changed to <strong>{{ $health }}</strong>.</p>
    <a href="{{ route('projects.show', $project) }}" class="button">View Project</a>
@endsection
