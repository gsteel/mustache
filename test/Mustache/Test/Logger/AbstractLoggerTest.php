<?php

namespace Mustache\Test\Logger;

use Mustache\Logger;
use PHPUnit\Framework\TestCase;

class AbstractLoggerTest extends TestCase
{
    public function testEverything()
    {
        $logger = new TestLogger();

        $logger->emergency('emergency message');
        $logger->alert('alert message');
        $logger->critical('critical message');
        $logger->error('error message');
        $logger->warning('warning message');
        $logger->notice('notice message');
        $logger->info('info message');
        $logger->debug('debug message');

        $expected = [
            [Logger::EMERGENCY, 'emergency message', []],
            [Logger::ALERT, 'alert message', []],
            [Logger::CRITICAL, 'critical message', []],
            [Logger::ERROR, 'error message', []],
            [Logger::WARNING, 'warning message', []],
            [Logger::NOTICE, 'notice message', []],
            [Logger::INFO, 'info message', []],
            [Logger::DEBUG, 'debug message', []],
        ];

        $this->assertEquals($expected, $logger->log);
    }
}
