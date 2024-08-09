<?php

namespace Mustache\Exception;

use Mustache\Exception;
use Throwable;

/**
 * Unknown helper exception.
 */
class UnknownHelperException extends InvalidArgumentException implements Exception
{
    protected $helperName;

    /**
     * @param string    $helperName
     */
    public function __construct($helperName, Throwable $previous = null)
    {
        $this->helperName = $helperName;
        $message = sprintf('Unknown helper: %s', $helperName);
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($message, 0, $previous);
        } else {
            parent::__construct($message); // @codeCoverageIgnore
        }
    }

    public function getHelperName()
    {
        return $this->helperName;
    }
}
