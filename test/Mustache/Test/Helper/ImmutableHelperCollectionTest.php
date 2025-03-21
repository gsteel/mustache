<?php

declare(strict_types=1);

namespace Mustache\Test\Helper;

use Mustache\Exception\UnknownHelperException;
use Mustache\Helper\ImmutableHelperCollection;
use PHPUnit\Framework\TestCase;

final class ImmutableHelperCollectionTest extends TestCase
{
    public function testHas(): void
    {
        $helpers = new ImmutableHelperCollection(['foo' => 'bar']);

        self::assertTrue($helpers->has('foo'));
        self::assertTrue(isset($helpers->foo));

        self::assertFalse($helpers->has('bar'));
        self::assertFalse(isset($helpers->bar));
    }

    public function testGet(): void
    {
        $helpers = new ImmutableHelperCollection(['foo' => 'bar']);

        self::assertSame('bar', $helpers->get('foo'));
        self::assertSame('bar', $helpers->__get('foo'));
    }

    public function testExceptionThrownAccessingNonExistentHelper(): void
    {
        $helpers = new ImmutableHelperCollection([]);
        $this->expectException(UnknownHelperException::class);
        $helpers->__get('foo');
    }

    public function testIsEmpty(): void
    {
        $helpers = new ImmutableHelperCollection([]);
        self::assertTrue($helpers->isEmpty());

        $helpers = new ImmutableHelperCollection(['foo' => 'bar']);
        self::assertFalse($helpers->isEmpty());
    }
}
