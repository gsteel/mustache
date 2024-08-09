<?php

declare(strict_types=1);

namespace Mustache\Test\Exception;

use Exception;
use Mustache\Exception\UnknownTemplateException;
use PHPUnit\Framework\TestCase;

class UnknownTemplateExceptionTest extends TestCase
{
    public function testMessage(): void
    {
        $e = new UnknownTemplateException('luigi');
        $this->assertEquals('Unknown template: luigi', $e->getMessage());
    }

    public function testGetTemplateName(): void
    {
        $e = new UnknownTemplateException('yoshi');
        $this->assertEquals('yoshi', $e->getTemplateName());
    }

    public function testPrevious(): void
    {
        $previous = new Exception();
        $e = new UnknownTemplateException('foo', $previous);
        $this->assertSame($previous, $e->getPrevious());
    }
}
