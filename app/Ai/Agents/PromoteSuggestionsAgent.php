<?php

namespace App\Ai\Agents;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4.1')]
class PromoteSuggestionsAgent implements Agent, HasStructuredOutput
{
    use Promptable;

    public function __construct(private string $projectGuide) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        $guide = $this->projectGuide;

        return <<<INSTRUCTIONS
        You are a senior software developer estimating and refining tasks. Given a backlog item title, description, and project context, you suggest a complexity weight, estimated implementation hours, and a refined task description with technical context.

        Project Guide:
        {$guide}
        INSTRUCTIONS;
    }

    /**
     * Get the agent's structured output schema definition.
     */
    public function schema(JsonSchema $schema): array
    {
        return [
            'type'            => $schema->string()->enum(['feature', 'bug', 'change'])->required(),
            'priority'        => $schema->string()->enum(['low', 'medium', 'high'])->required(),
            'weight'          => $schema->integer()->min(1)->max(5)->required(),
            'weight_reason'   => $schema->string()->required(),
            'estimated_hours' => $schema->integer()->min(1)->required(),
            'description'     => $schema->string()->required(),
        ];
    }
}
