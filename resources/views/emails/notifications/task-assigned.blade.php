@extends('emails.layout')

@section('content')
    <h1>You have been assigned a task</h1>
    <p>Hi {{ $user->name }},</p>
    <p><strong>{{ $assigner->name }}</strong> has assigned you a task on <strong>{{ $task->project?->name }}</strong>.</p>
    <div class="meta">
        <p><strong>Task:</strong> {{ $task->title }}</p>
        <p><strong>Priority:</strong> {{ ucfirst($task->priority) }}</p>
        @if($task->due_date)
            <p><strong>Due:</strong> {{ $task->due_date->format('M d, Y') }}</p>
        @endif
    </div>
    <a href="{{ route('tasks.show', $task) }}" class="button">View Task</a>
@endsection
