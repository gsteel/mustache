<?php

declare(strict_types=1);

namespace Mustache\Exception;

use Mustache\Exception;
use Throwable;
use UnexpectedValueException;

use function sprintf;
use function version_compare;

use const PHP_VERSION;

/**
 * Unknown filter exception.
 */
class UnknownFilterException extends UnexpectedValueException implements Exception
{
    public function __construct(private string $filterName, Throwable|null $previous = null)
    {
        $message = sprintf('Unknown filter: %s', $filterName);
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message); // @codeCoverageIgnore
        }
    }

    public function getFilterName(): string
    {
        return $this->filterName;
    }
}
