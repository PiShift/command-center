@extends('emails.layout')

@section('content')
    <h1>Task status changed</h1>
    <p>Hi {{ $user->name }},</p>
    <p>The status of a task you are involved with has been updated by <strong>{{ $changer->name }}</strong>.</p>
    <div class="meta">
        <p><strong>Task:</strong> {{ $task->title }}</p>
        <p><strong>From:</strong> {{ $oldStatus }}</p>
        <p><strong>To:</strong> {{ $newStatus }}</p>
        <p><strong>Project:</strong> {{ $task->project?->name }}</p>
    </div>
    <a href="{{ route('tasks.show', $task) }}" class="button">View Task</a>
@endsection
