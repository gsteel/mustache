<?php

declare(strict_types=1);

namespace Mustache\Test\Exception;

use Exception;
use Mustache\Exception\UnknownFilterException;
use PHPUnit\Framework\TestCase;

class UnknownFilterExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $e = new UnknownFilterException('sausage');
        $this->assertEquals('Unknown filter: sausage', $e->getMessage());
    }

    public function testGetFilterName(): void
    {
        $e = new UnknownFilterException('eggs');
        $this->assertEquals('eggs', $e->getFilterName());
    }

    public function testPrevious(): void
    {
        $previous = new Exception();
        $e = new UnknownFilterException('foo', $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
