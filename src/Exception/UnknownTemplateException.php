<?php

declare(strict_types=1);

namespace Mustache\Exception;

use Mustache\Exception;
use Throwable;

use function sprintf;
use function version_compare;

use const PHP_VERSION;

/**
 * Unknown template exception.
 */
class UnknownTemplateException extends InvalidArgumentException implements Exception
{
    public function __construct(private string $templateName, Throwable|null $previous = null)
    {
        $message = sprintf('Unknown template: %s', $templateName);
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message); // @codeCoverageIgnore
        }
    }

    public function getTemplateName(): string
    {
        return $this->templateName;
    }
}
