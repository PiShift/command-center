@extends('emails.layout')

@section('content')
    <h1>You are now the lead of {{ $team->name }}</h1>
    <p>Hi {{ $user->name }},</p>
    <p>You have been assigned as the team lead of <strong>{{ $team->name }}</strong>. You can now manage the team's members and settings.</p>
    <a href="{{ route('teams.show', $team) }}" class="button">View Team</a>
@endsection
