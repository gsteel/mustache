<?php

namespace Mustache\Exception;

use Mustache\Exception;
use Throwable;

/**
 * Mustache syntax exception.
 */
class SyntaxException extends LogicException implements Exception
{
    protected $token;

    /**
     * @param string    $msg
     * @param array     $token
     */
    public function __construct($msg, array $token, Throwable $previous = null)
    {
        $this->token = $token;
        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            parent::__construct($msg, 0, $previous);
        } else {
            parent::__construct($msg); // @codeCoverageIgnore
        }
    }

    /**
     * @return array
     */
    public function getToken()
    {
        return $this->token;
    }
}
