<?php

namespace Mustache\Test\Logger;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\LogicException;
use Mustache\Logger;
use Mustache\Logger\StreamLogger;
use PHPUnit\Framework\TestCase;

class StreamLoggerTest extends TestCase
{
    /**
     * @dataProvider acceptsStreamData
     */
    public function testAcceptsStream($name, $stream)
    {
        $logger = new StreamLogger($stream);
        $logger->log(Logger::CRITICAL, 'message');

        $this->assertEquals("CRITICAL: message\n", file_get_contents($name));
    }

    public function acceptsStreamData()
    {
        $one = tempnam(sys_get_temp_dir(), 'mustache-test');
        $two = tempnam(sys_get_temp_dir(), 'mustache-test');

        return array(
            array($one, $one),
            array($two, fopen($two, 'a')),
        );
    }

    public function testPrematurelyClosedStreamThrowsException()
    {
        $stream = tmpfile();
        $logger = new StreamLogger($stream);
        fclose($stream);

        $this->expectException(LogicException::class);
        $logger->log(Logger::CRITICAL, 'message');
    }

    /**
     * @dataProvider getLevels
     */
    public function testLoggingThresholds($logLevel, $level, $shouldLog)
    {
        $stream = tmpfile();
        $logger = new StreamLogger($stream, $logLevel);
        $logger->log($level, 'logged');

        rewind($stream);
        $result = fread($stream, 1024);

        if ($shouldLog) {
            $this->assertStringContainsString('logged', $result);
        } else {
            $this->assertEmpty($result);
        }
    }

    public function getLevels()
    {
        // $logLevel, $level, $shouldLog
        return array(
            // identities
            array(Logger::EMERGENCY, Logger::EMERGENCY, true),
            array(Logger::ALERT,     Logger::ALERT,     true),
            array(Logger::CRITICAL,  Logger::CRITICAL,  true),
            array(Logger::ERROR,     Logger::ERROR,     true),
            array(Logger::WARNING,   Logger::WARNING,   true),
            array(Logger::NOTICE,    Logger::NOTICE,    true),
            array(Logger::INFO,      Logger::INFO,      true),
            array(Logger::DEBUG,     Logger::DEBUG,     true),

            // one above
            array(Logger::ALERT,     Logger::EMERGENCY, true),
            array(Logger::CRITICAL,  Logger::ALERT,     true),
            array(Logger::ERROR,     Logger::CRITICAL,  true),
            array(Logger::WARNING,   Logger::ERROR,     true),
            array(Logger::NOTICE,    Logger::WARNING,   true),
            array(Logger::INFO,      Logger::NOTICE,    true),
            array(Logger::DEBUG,     Logger::INFO,      true),

            // one below
            array(Logger::EMERGENCY, Logger::ALERT,     false),
            array(Logger::ALERT,     Logger::CRITICAL,  false),
            array(Logger::CRITICAL,  Logger::ERROR,     false),
            array(Logger::ERROR,     Logger::WARNING,   false),
            array(Logger::WARNING,   Logger::NOTICE,    false),
            array(Logger::NOTICE,    Logger::INFO,      false),
            array(Logger::INFO,      Logger::DEBUG,     false),
        );
    }

    /**
     * @dataProvider getLogMessages
     */
    public function testLogging($level, $message, $context, $expected)
    {
        $stream = tmpfile();
        $logger = new StreamLogger($stream, Logger::DEBUG);
        $logger->log($level, $message, $context);

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertEquals($expected, $result);
    }

    public function getLogMessages()
    {
        // $level, $message, $context, $expected
        return array(
            array(Logger::DEBUG,     'debug message',     array(),  "DEBUG: debug message\n"),
            array(Logger::INFO,      'info message',      array(),  "INFO: info message\n"),
            array(Logger::NOTICE,    'notice message',    array(),  "NOTICE: notice message\n"),
            array(Logger::WARNING,   'warning message',   array(),  "WARNING: warning message\n"),
            array(Logger::ERROR,     'error message',     array(),  "ERROR: error message\n"),
            array(Logger::CRITICAL,  'critical message',  array(),  "CRITICAL: critical message\n"),
            array(Logger::ALERT,     'alert message',     array(),  "ALERT: alert message\n"),
            array(Logger::EMERGENCY, 'emergency message', array(),  "EMERGENCY: emergency message\n"),

            // with context
            array(
                Logger::ERROR,
                'error message',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error message\n",
            ),

            // with interpolation
            array(
                Logger::ERROR,
                'error {name}-{number}',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error foo-42\n",
            ),

            // with iterpolation false positive
            array(
                Logger::ERROR,
                'error {nothing}',
                array('name' => 'foo', 'number' => 42),
                "ERROR: error {nothing}\n",
            ),

            // with interpolation injection
            array(
                Logger::ERROR,
                '{foo}',
                array('foo' => '{bar}', 'bar' => 'FAIL'),
                "ERROR: {bar}\n",
            ),
        );
    }

    public function testChangeLoggingLevels()
    {
        $stream = tmpfile();
        $logger = new StreamLogger($stream);

        $logger->setLevel(Logger::ERROR);
        $this->assertEquals(Logger::ERROR, $logger->getLevel());

        $logger->log(Logger::WARNING, 'ignore this');

        $logger->setLevel(Logger::INFO);
        $this->assertEquals(Logger::INFO, $logger->getLevel());

        $logger->log(Logger::WARNING, 'log this');

        $logger->setLevel(Logger::CRITICAL);
        $this->assertEquals(Logger::CRITICAL, $logger->getLevel());

        $logger->log(Logger::ERROR, 'ignore this');

        rewind($stream);
        $result = fread($stream, 1024);

        $this->assertEquals("WARNING: log this\n", $result);
    }

    public function testThrowsInvalidArgumentExceptionWhenSettingUnknownLevels()
    {
        $logger = new StreamLogger(tmpfile());
        $this->expectException(InvalidArgumentException::class);
        $logger->setLevel('bacon');
    }

    public function testThrowsInvalidArgumentExceptionWhenLoggingUnknownLevels()
    {
        $logger = new StreamLogger(tmpfile());
        $this->expectException(InvalidArgumentException::class);
        $logger->log('bacon', 'CODE BACON ERROR!');
    }
}
