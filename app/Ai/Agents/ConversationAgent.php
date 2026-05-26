<?php

namespace App\Ai\Agents;

use App\Models\Project;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\Conversational;
use Laravel\Ai\Messages\Message;
use Laravel\Ai\Promptable;

class ConversationAgent implements Agent, Conversational
{
    use Promptable;

    /**
     * @param  array<int, array{role: string, content: string}>  $history
     */
    public function __construct(
        private Project $project,
        private string $additionalContext = '',
        private array $history = [],
    ) {}

    /**
     * Get the agent's system instructions.
     */
    public function instructions(): string
    {
        $name    = $this->project->name;
        $guide   = $this->project->guide ?? 'No guide provided.';
        $context = $this->additionalContext ?: 'None.';

        return <<<INSTRUCTIONS
        You are an AI assistant embedded in a project management tool for software teams.

        Project: {$name}

        Project Guide:
        {$guide}

        Additional Context:
        {$context}

        Your role is to help the team with planning, answering questions about the project, suggesting tasks, helping break down features into sprints, and providing technical guidance. Be concise, actionable, and grounded in the project context.

        When you are explicitly suggesting tasks, sprints, or backlog items to create, append a structured block at the very end of your response using this exact format:

        <actions>
        {"type":"tasks","items":[{"title":"","description":"","type":"feature","weight":2,"priority":"medium"}]}
        </actions>

        Rules for the <actions> block:
        - Use type "tasks" when suggesting individual work items or tasks.
        - Use type "sprints" when suggesting sprint names/goals to create.
        - Use type "backlog" when suggesting items for the product backlog.
        - Item fields: title (required), description (optional), type (feature|bug|change), weight (1-5), priority (low|medium|high).
        - Only append <actions> when you are explicitly recommending items to create. Do NOT append it for general answers, explanations, or analysis.
        - The JSON must be valid and on a single line inside the <actions> tags.
        INSTRUCTIONS;
    }

    /**
     * Provide the conversation history to the AI provider.
     *
     * @return Message[]
     */
    public function messages(): iterable
    {
        return array_map(
            fn (array $m) => new Message($m['role'], $m['content']),
            $this->history
        );
    }
}
