@extends('emails.layout')

@section('content')
    <h1>Task overdue</h1>
    <p>Hi {{ $user->name }},</p>
    <p>A task assigned to you is <strong>{{ $daysOverdue }} day(s) overdue</strong>.</p>
    <div class="meta">
        <p><strong>Task:</strong> {{ $task->title }}</p>
        <p><strong>Project:</strong> {{ $task->project?->name }}</p>
        <p><strong>Due date:</strong> {{ $task->due_date?->format('M d, Y') }}</p>
    </div>
    <a href="{{ route('tasks.show', $task) }}" class="button">View Task</a>
@endsection
