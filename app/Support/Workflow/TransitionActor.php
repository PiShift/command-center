<?php

namespace App\Support\Workflow;

use App\Models\User;

/**
 * Who is attempting a task status transition: an authenticated user, a
 * daemon/agent, or the system. Conditions in config/task-workflow.php
 * evaluate against this.
 */
class TransitionActor
{
    private function __construct(
        public readonly string $type,
        public readonly ?int $userId = null,
        public readonly ?string $agentId = null,
        public readonly ?string $label = null,
        private readonly ?User $user = null,
    ) {}

    public static function user(User $user): self
    {
        return new self('user', userId: $user->id, user: $user);
    }

    public static function agent(?string $agentId, ?string $label = null): self
    {
        return new self('daemon', agentId: $agentId, label: $label ?? 'Daemon runtime');
    }

    public static function system(?string $label = null): self
    {
        return new self('system', label: $label ?? 'System');
    }

    /**
     * Resolve from the current auth context (web session, PAT session, or
     * daemon bearer token). Pass an explicit actor when the context differs
     * (e.g. daemon endpoints acting on behalf of an agent).
     */
    public static function current(): self
    {
        $authUser = auth()->user();

        if ($authUser) {
            return self::user($authUser);
        }

        if (app()->bound('request')) {
            $authorization = (string) request()->header('Authorization', '');

            if (str_starts_with($authorization, 'Bearer mat_')) {
                return self::agent(null, 'Daemon API');
            }
        }

        return self::system();
    }

    public function isUser(): bool
    {
        return $this->type === 'user';
    }

    public function hasPermission(string $slug): bool
    {
        return $this->user?->hasPermission($slug) ?? false;
    }

    /**
     * Payload shape expected by TaskStatusHistoryLogger::log().
     *
     * @return array{type: string, user_id: ?int, agent_id: ?string, label: ?string}
     */
    public function toHistoryActor(): array
    {
        return [
            'type' => $this->type,
            'user_id' => $this->userId,
            'agent_id' => $this->agentId,
            'label' => $this->label,
        ];
    }
}
