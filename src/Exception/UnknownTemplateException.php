<?php

namespace Mustache\Exception;

use Mustache\Exception;
use Throwable;

/**
 * Unknown template exception.
 */
class UnknownTemplateException extends InvalidArgumentException implements Exception
{
    protected $templateName;

    /**
     * @param string    $templateName
     */
    public function __construct($templateName, Throwable $previous = null)
    {
        $this->templateName = $templateName;
        $message = sprintf('Unknown template: %s', $templateName);
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message); // @codeCoverageIgnore
        }
    }

    public function getTemplateName()
    {
        return $this->templateName;
    }
}
