<?php

namespace App\Ai\Agents;

use App\Ai\Middleware\LogAiPrompts;
use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasMiddleware;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4.1')]
class BacklogPlannerAgent implements Agent, HasStructuredOutput, HasMiddleware
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
        You are an expert product manager and software architect. You receive raw feature requests or notes and a project guide. You organize the features into logical sprints grouped by theme and complexity. You name each sprint using the version + theme convention (e.g. v1.0 — Core Foundation, v2.0 — QCM System). You provide a clear rationale for each grouping.

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
            'sprints' => $schema->array()->items(
                $schema->object([
                    'name'      => $schema->string()->required(),
                    'rationale' => $schema->string()->required(),
                    'items'     => $schema->array()->items(
                        $schema->object([
                            'title'            => $schema->string()->required(),
                            'description'      => $schema->string()->required(),
                            'type'             => $schema->string()->enum(['feature', 'bug', 'change'])->required(),
                            'suggested_weight' => $schema->integer()->min(1)->max(5)->required(),
                        ])
                    )->required(),
                ])
            )->required(),
        ];
    }

    /**
     * Get the agent's middleware.
     */
    public function middleware(): array
    {
        return [new LogAiPrompts];
    }
}
