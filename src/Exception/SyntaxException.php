<?php

declare(strict_types=1);

namespace Mustache\Exception;

use Mustache\Exception;
use Throwable;

/**
 * Mustache syntax exception.
 */
class SyntaxException extends LogicException implements Exception
{
    /** @var array<string, mixed> */
    private array $token;

    /** @param array<string, mixed> $token */
    public function __construct(string $msg, array $token, ?Throwable $previous = null)
    {
        $this->token = $token;

        parent::__construct($msg, 0, $previous);
    }

    /** @return array<string, mixed> */
    public function getToken(): array
    {
        return $this->token;
    }
}
