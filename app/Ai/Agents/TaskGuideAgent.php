<?php

namespace App\Ai\Agents;

use Laravel\Ai\Attributes\Model;
use Laravel\Ai\Attributes\Provider;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

#[Provider(Lab::OpenAI)]
#[Model('gpt-4.1')]
class TaskGuideAgent implements Agent
{
    use Promptable;

    public function __construct(
        private string $projectGuide,
        private string $sprintName = '',
    ) {}

    /**
     * Get the instructions that the agent should follow.
     */
    public function instructions(): string
    {
        $guide      = $this->projectGuide;
        $sprintName = $this->sprintName;

        return <<<INSTRUCTIONS
        You are a senior developer writing implementation guides for your team. Given a task title, description, type, priority, sprint name, and project guide, you write a detailed markdown implementation guide. Include: what to build, where in the codebase (reference file paths from the project guide), step by step technical approach, and clear acceptance criteria. Write as if explaining to a junior developer.

        Sprint: {$sprintName}

        Project Guide:
        {$guide}
        INSTRUCTIONS;
    }
}
