<?php

declare(strict_types=1);

namespace Mustache\Cache;

use Mustache\Cache;
use Psr\Log\LoggerInterface;

/**
 * Abstract Mustache Cache class.
 *
 * Provides logging support to child implementations.
 *
 * @abstract
 */
abstract class AbstractCache implements Cache
{
    private ?LoggerInterface $logger = null;

    /**
     * Get the current logger instance.
     */
    public function getLogger(): ?LoggerInterface
    {
        return $this->logger;
    }

    public function setLogger(?LoggerInterface $logger = null): void
    {
        $this->logger = $logger;
    }

    /**
     * Add a log record if logging is enabled.
     *
     * @param string $level   The logging level
     * @param string $message The log message
     * @param array<string, mixed> $context The log context
     */
    protected function log(string $level, string $message, array $context = []): void
    {
        if (! isset($this->logger)) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }
}
