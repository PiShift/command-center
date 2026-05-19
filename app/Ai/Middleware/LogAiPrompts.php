<?php

namespace App\Ai\Middleware;

use Closure;
use Illuminate\Support\Facades\Log;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;

class LogAiPrompts
{
    /**
     * Handle the incoming prompt — log both the prompt and the response.
     */
    public function handle(AgentPrompt $prompt, Closure $next)
    {
        Log::info('AI prompt dispatched', [
            'agent'  => class_basename($prompt->agent),
            'prompt' => $prompt->prompt,
        ]);

        return $next($prompt)->then(function (AgentResponse $response) use ($prompt) {
            Log::info('AI response received', [
                'agent' => class_basename($prompt->agent),
                'text'  => $response->text,
            ]);
        });
    }
}
