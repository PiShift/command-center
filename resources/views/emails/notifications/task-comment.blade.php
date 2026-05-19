@extends('emails.layout')

@section('content')
    <h1>New comment on task</h1>
    <p>Hi {{ $user->name }},</p>
    <p><strong>{{ $commenter->name }}</strong> commented on <strong>{{ $task->title }}</strong>:</p>
    <div class="meta">
        <p>{{ $comment->body }}</p>
    </div>
    <a href="{{ route('tasks.show', $task) }}" class="button">View Task</a>
@endsection
