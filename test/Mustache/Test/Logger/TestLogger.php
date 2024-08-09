<?php

declare(strict_types=1);

namespace Mustache\Test\Logger;

use Mustache\Logger\AbstractLogger;

final class TestLogger extends AbstractLogger
{
    public $log = array();

    /**
     * Logs with an arbitrary level.
     *
     * @param mixed  $level
     * @param string $message
     * @param array  $context
     */
    public function log($level, $message, array $context = array())
    {
        $this->log[] = array($level, $message, $context);
    }
}
