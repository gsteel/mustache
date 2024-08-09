<?php

namespace Mustache\Test\Exception;

use Mustache\Exception;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownTemplateException;
use PHPUnit\Framework\TestCase;

class Mustache_Test_Exception_UnknownTemplateExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new UnknownTemplateException('mario');
        $this->assertTrue($e instanceof InvalidArgumentException);
        $this->assertTrue($e instanceof Exception);
    }

    public function testMessage()
    {
        $e = new UnknownTemplateException('luigi');
        $this->assertEquals('Unknown template: luigi', $e->getMessage());
    }

    public function testGetTemplateName()
    {
        $e = new UnknownTemplateException('yoshi');
        $this->assertEquals('yoshi', $e->getTemplateName());
    }

    public function testPrevious()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped('Mustache\Exception chaining requires at least PHP 5.3');
        }

        $previous = new \Exception();
        $e = new UnknownTemplateException('foo', $previous);
        $this->assertSame($previous, $e->getPrevious());
    }
}
