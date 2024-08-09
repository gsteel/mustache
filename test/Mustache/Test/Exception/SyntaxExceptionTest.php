<?php

declare(strict_types=1);

namespace Mustache\Test\Exception;

use Exception;
use Mustache\Exception\SyntaxException;
use Mustache\Tokenizer;
use PHPUnit\Framework\TestCase;

class SyntaxExceptionTest extends TestCase
{
    public function testGetToken(): void
    {
        $token = [Tokenizer::TYPE => 'whatever'];
        $e = new SyntaxException('ignore this', $token);
        $this->assertEquals($token, $e->getToken());
    }

    public function testPrevious(): void
    {
        $previous = new Exception();
        $e = new SyntaxException('foo', [], $previous);

        $this->assertSame($previous, $e->getPrevious());
    }
}
