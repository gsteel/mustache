<?php

declare(strict_types=1);

namespace Mustache\Exception;

use Mustache\Exception;
use Throwable;

use function sprintf;
use function version_compare;

use const PHP_VERSION;

/**
 * Unknown helper exception.
 */
class UnknownHelperException extends InvalidArgumentException implements Exception
{
    public function __construct(private string $helperName, Throwable|null $previous = null)
    {
        $message = sprintf('Unknown helper: %s', $helperName);
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message); // @codeCoverageIgnore
        }
    }

    public function getHelperName(): string
    {
        return $this->helperName;
    }
}
