<?php

namespace Mustache\Test\Exception;

use Mustache\Exception;
use Mustache\Exception\UnknownFilterException;
use PHPUnit\Framework\TestCase;
use UnexpectedValueException;

class UnknownFilterExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new UnknownFilterException('bacon');
        $this->assertTrue($e instanceof UnexpectedValueException);
        $this->assertTrue($e instanceof Exception);
    }

    public function testMessage()
    {
        $e = new UnknownFilterException('sausage');
        $this->assertEquals('Unknown filter: sausage', $e->getMessage());
    }

    public function testGetFilterName()
    {
        $e = new UnknownFilterException('eggs');
        $this->assertEquals('eggs', $e->getFilterName());
    }

    public function testPrevious()
    {
        $previous = new \Exception();
        $e = new UnknownFilterException('foo', $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
