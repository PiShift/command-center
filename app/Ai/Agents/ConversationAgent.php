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
        private array $documents = [],
        private string $additionalContext = '',
        private array $history = [],
        private string $statusSnapshot = '',
    ) {}

    /**
     * Get the agent's system instructions.
     */
    public function instructions(): string
    {
        $name    = $this->project->name;
        $documents = empty($this->documents)
            ? 'No project documents provided.'
            : collect($this->documents)->map(function (array $doc): string {
                $title = trim((string) ($doc['title'] ?? 'Untitled'));
                $type = trim((string) ($doc['type'] ?? ''));
                $content = trim((string) ($doc['content'] ?? ''));
                $heading = $type !== '' ? "## {$title} ({$type})" : "## {$title}";

                return $heading . "\n" . ($content !== '' ? $content : 'No content.');
            })->implode("\n\n");
        $context = $this->additionalContext ?: 'None.';
        $snapshot = $this->statusSnapshot !== '' ? "\n\n" . trim($this->statusSnapshot) : '';

        return <<<INSTRUCTIONS
        You are an AI assistant embedded in a project management tool for software teams.

        Project: {$name}

        Project Documents:
        {$documents}

        Additional Context:
        {$context}{$snapshot}

        Your role is to help the team with planning, answering questions about the project, suggesting tasks, helping break down features into sprints, and providing technical guidance. Be concise, actionable, and grounded in the project context.

                When you are explicitly suggesting tasks, sprints, or backlog items to create, append a structured block at the very end of your response using this exact format:

        <actions>
        {"type":"tasks","items":[{"title":"","description":"","type":"feature","weight":2,"priority":"medium"}]}
        </actions>

        Rules for the <actions> block:
                - Think in delivery hierarchy: sprints contain tasks, and tasks can contain checklist items.
                - Use type "sprint_with_tasks" when proposing a sprint and its tasks in one batch.
                - sprint_with_tasks payload must be:
                    {"type":"sprint_with_tasks","sprint_name":"","sprint_description":"","tasks":[{"title":"","description":"","type":"feature","priority":"medium","weight":2,"checklist":["item 1","item 2"]}]}
        - Use type "tasks" when suggesting individual work items or tasks.
        - Use type "sprints" when suggesting sprint names/goals to create.
        - Use type "backlog" when suggesting items for the product backlog.
                - When you need clarification before creating tasks or sprints, gather ALL questions at once in one clarification block. Never ask one question at a time across multiple messages.
                - Use type "clarification" for multi-question clarifications with this shape:
                    {"type":"clarification","questions":[{"id":"scope","text":"...","type":"pills","options":["..."],"allow_other":true,"required":true}]}
                - Clarification question fields: id (unique key), text, type (pills|text|multiselect), options (for pills/multiselect), allow_other (for pills), required (boolean).
                - Use type "question" only for a single, standalone clarification when truly necessary.
                - Task item fields: title (required), description (optional), type (feature|bug|change), weight (1-5), priority (low|medium|high), checklist (optional array of strings).
                - For "tasks" actions: include checklist when a task is complex or multi-step.
        - Question fields: question (required), input_type (pills|text|multiselect|form), options (optional array), allow_custom (optional boolean), form (optional array of fields).
        - When asking a question, keep the prose response brief and put the interactive prompt in the <actions> block.
        - Only append <actions> when you are explicitly recommending items to create. Do NOT append it for general answers, explanations, or analysis.
        - The JSON must be valid and on a single line inside the <actions> tags.

        Example question action:
        <actions>
        {"type":"question","question":"Which sprint should these tasks target?","input_type":"pills","options":["Current sprint","Next sprint","Backlog"],"allow_custom":true}
        </actions>
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
