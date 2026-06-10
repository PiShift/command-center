<?php

namespace App\Http\Controllers;

use App\Ai\Agents\ConversationAgent;
use App\Models\AiConversation;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\RateLimiter;
use Laravel\Ai\Streaming\Events\TextDelta;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AiConversationController extends Controller
{
    /**
     * POST /ai/conversation/stream
     *
     * Streams an AI response for the given project conversation via Server-Sent Events.
     */
    public function stream(Request $request): StreamedResponse
    {
        $data = $request->validate([
            'project_id'      => ['required', 'integer', 'exists:projects,id'],
            'message'         => ['required', 'string', 'max:10000'],
            'conversation_id' => ['nullable', 'integer', 'exists:ai_conversations,id'],
            'context'         => ['nullable', 'string', 'max:50000'],
            'status_snapshot' => ['nullable', 'string', 'max:5000'],
        ]);

        $project = Project::findOrFail($data['project_id']);
        Gate::authorize('view', $project);

        // Rate-limit to 30 requests per minute per user.
        $key = 'ai-stream:' . $request->user()->id;
        if (RateLimiter::tooManyAttempts($key, 30)) {
            abort(429, 'Too many AI requests. Please wait a moment.');
        }
        RateLimiter::hit($key, 60);

        // Load prior conversation history (excluding the just-saved user message).
        $history = [];
        if (! empty($data['conversation_id'])) {
            $conversation = AiConversation::where('id', $data['conversation_id'])
                ->where('user_id', $request->user()->id)
                ->first();

            if ($conversation) {
                $history = $conversation->messages()
                    ->get(['role', 'content'])
                    ->map(fn ($m) => ['role' => $m->role, 'content' => $m->content])
                    ->toArray();

                // The last entry is the user message we just saved — strip it so it
                // doesn't appear twice (it's passed as the prompt below instead).
                if (! empty($history) && end($history)['role'] === 'user') {
                    array_pop($history);
                }
            }
        }

        $documents = $project->projectDocuments()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get(['title', 'type', 'content'])
            ->map(fn ($doc) => [
                'title' => (string) $doc->title,
                'type' => (string) ($doc->type ?? ''),
                'content' => (string) ($doc->content ?? ''),
            ])
            ->toArray();

        $agent   = new ConversationAgent($project, $documents, $data['context'] ?? '', $history, $data['status_snapshot'] ?? '');
        $message = $data['message'];

        return new StreamedResponse(function () use ($agent, $message) {
            $stream = $agent->stream($message);
            $fullResponse = '';

            foreach ($stream as $event) {
                if ($event instanceof TextDelta) {
                    $fullResponse .= $event->delta;
                    echo 'data: ' . json_encode(['chunk' => $event->delta]) . "\n\n";
                    ob_flush();
                    flush();
                }
            }

            // Final parse pass to ensure actions are extracted even when stream chunking
            // causes partial tags during incremental rendering.
            if (preg_match('/<actions>(.*?)<\/actions>/s', $fullResponse, $matches)) {
                $jsonStr = trim($matches[1]);
                $jsonStr = preg_replace('/^```(?:json)?\s*/m', '', $jsonStr);
                $jsonStr = preg_replace('/```\s*$/m', '', $jsonStr);
                $jsonStr = trim($jsonStr);

                $decoded = json_decode($jsonStr, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $intro = trim((string) preg_replace('/<actions>.*?<\/actions>/s', '', $fullResponse));

                    echo 'data: ' . json_encode([
                        'actions_parsed' => [
                            'actions' => $decoded,
                            'intro'   => $intro,
                        ],
                    ]) . "\n\n";
                    ob_flush();
                    flush();
                }
            }

            echo "data: [DONE]\n\n";
            ob_flush();
            flush();
        }, 200, [
            'Content-Type'      => 'text/event-stream',
            'Cache-Control'     => 'no-cache, no-store',
            'X-Accel-Buffering' => 'no',
        ]);
    }
}
