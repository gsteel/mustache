<?php

declare(strict_types=1);

namespace Mustache\Test\Exception;

use Exception;
use Mustache\Exception\UnknownHelperException;
use PHPUnit\Framework\TestCase;

class UnknownHelperExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $e = new UnknownHelperException('beta');
        $this->assertEquals('Unknown helper: beta', $e->getMessage());
    }

    public function testGetHelperName(): void
    {
        $e = new UnknownHelperException('gamma');
        $this->assertEquals('gamma', $e->getHelperName());
    }

    public function testPrevious(): void
    {
        $previous = new Exception();
        $e = new UnknownHelperException('foo', $previous);
        $this->assertSame($previous, $e->getPrevious());
    }
}
