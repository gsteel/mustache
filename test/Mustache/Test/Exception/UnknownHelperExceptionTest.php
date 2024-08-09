<?php

namespace Mustache\Test\Exception;

use Mustache\Exception;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownHelperException;
use PHPUnit\Framework\TestCase;

class Mustache_Test_Exception_UnknownHelperExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new UnknownHelperException('alpha');
        $this->assertTrue($e instanceof InvalidArgumentException);
        $this->assertTrue($e instanceof Exception);
    }

    public function testMessage()
    {
        $e = new UnknownHelperException('beta');
        $this->assertEquals('Unknown helper: beta', $e->getMessage());
    }

    public function testGetHelperName()
    {
        $e = new UnknownHelperException('gamma');
        $this->assertEquals('gamma', $e->getHelperName());
    }

    public function testPrevious()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped('Mustache\Exception chaining requires at least PHP 5.3');
        }

        $previous = new \Exception();
        $e = new UnknownHelperException('foo', $previous);
        $this->assertSame($previous, $e->getPrevious());
    }
}
