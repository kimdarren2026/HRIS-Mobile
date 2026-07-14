<?php

namespace App\Exceptions\Authentication;

use RuntimeException;
use Throwable;

class ExternalAuthenticationException extends RuntimeException
{
    public function __construct(
        private readonly string $userMessage,
        private readonly string $reason,
        private readonly array $context = [],
        ?Throwable $previous = null,
    ) {
        parent::__construct($reason, 0, $previous);
    }

    public function userMessage(): string
    {
        return $this->userMessage;
    }

    public function reason(): string
    {
        return $this->reason;
    }

    public function context(): array
    {
        return $this->context;
    }
}
