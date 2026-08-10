<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when a task status transition is rejected by TaskStatusService —
 * either because the transition is not in the legal map, a condition failed,
 * or a validator failed. `stage` identifies which gate rejected it.
 */
class InvalidTaskTransition extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $stage,
        public readonly string $fromStatus,
        public readonly string $toStatus,
    ) {
        parent::__construct($message);
    }

    public static function illegal(string $from, string $to): self
    {
        return new self(
            "Cannot move a task from '{$from}' to '{$to}': that transition is not allowed.",
            'legality',
            $from,
            $to,
        );
    }

    public static function conditionFailed(string $from, string $to, string $message): self
    {
        return new self($message, 'condition', $from, $to);
    }

    public static function validationFailed(string $from, string $to, string $message): self
    {
        return new self($message, 'validator', $from, $to);
    }
}
