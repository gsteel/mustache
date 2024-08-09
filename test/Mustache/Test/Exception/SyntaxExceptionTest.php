<?php

namespace Mustache\Test\Exception;

use Mustache\Exception;
use Mustache\Exception\LogicException;
use Mustache\Exception\SyntaxException;
use Mustache\Tokenizer;
use PHPUnit\Framework\TestCase;

class SyntaxExceptionTest extends TestCase
{
    public function testInstance()
    {
        $e = new SyntaxException('whot', array('is' => 'this'));
        $this->assertTrue($e instanceof LogicException);
        $this->assertTrue($e instanceof Exception);
    }

    public function testGetToken()
    {
        $token = array(Tokenizer::TYPE => 'whatever');
        $e = new SyntaxException('ignore this', $token);
        $this->assertEquals($token, $e->getToken());
    }

    public function testPrevious()
    {
        if (version_compare(PHP_VERSION, '5.3.0', '<')) {
            $this->markTestSkipped('Mustache\Exception chaining requires at least PHP 5.3');
        }

        $previous = new \Exception();
        $e = new SyntaxException('foo', array(), $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
