<?php

declare(strict_types=1);

namespace Mustache\Test\Helper;

use Mustache\Exception\UnknownHelperException;
use Mustache\Helper\ContainerBackedHelperCollection;
use PHPUnit\Framework\TestCase;

final class ContainerBackedHelperCollectionTest extends TestCase
{
    public function testHas(): void
    {
        $helpers = new ContainerBackedHelperCollection(
            ['foo' => 'bar'],
            new InMemoryContainer([
                'bar' => 'baz',
            ]),
        );

        self::assertTrue($helpers->has('foo'));
        self::assertTrue(isset($helpers->foo));

        self::assertFalse($helpers->has('bar'));
        self::assertFalse(isset($helpers->bar));
    }

    public function testHasConsultsTheContainer(): void
    {
        $helpers = new ContainerBackedHelperCollection(
            ['foo' => 'bar'],
            new InMemoryContainer([]),
        );

        self::assertFalse($helpers->has('foo'));
        self::assertFalse(isset($helpers->foo));
    }

    public function testGet(): void
    {
        $helpers = new ContainerBackedHelperCollection(
            ['foo' => 'bar'],
            new InMemoryContainer([
                'bar' => 'baz',
            ]),
        );

        self::assertSame('baz', $helpers->get('foo'));
        self::assertSame('baz', $helpers->__get('foo'));
    }

    public function testExceptionThrownAccessingUnmapped(): void
    {
        $helpers = new ContainerBackedHelperCollection(
            ['foo' => 'bar'],
            new InMemoryContainer([
                'bar' => 'baz',
            ]),
        );
        $this->expectException(UnknownHelperException::class);
        $helpers->__get('bing');
    }

    public function testExceptionThrownAccessingMappedHelperNotPresentInTheContainer(): void
    {
        $helpers = new ContainerBackedHelperCollection(
            ['foo' => 'bar'],
            new InMemoryContainer([]),
        );
        $this->expectException(UnknownHelperException::class);
        $helpers->__get('foo');
    }

    public function testIsEmpty(): void
    {
        $helpers = new ContainerBackedHelperCollection(
            [],
            new InMemoryContainer([]),
        );
        self::assertTrue($helpers->isEmpty());

        $helpers = new ContainerBackedHelperCollection(
            ['foo' => 'bar'],
            new InMemoryContainer([
                'bar' => 'baz',
            ]),
        );
        self::assertFalse($helpers->isEmpty());
    }

    public function testIsEmptyDoesNotCheckMappedServicesArePresentInTheContainer(): void
    {
        $helpers = new ContainerBackedHelperCollection(
            ['foo' => 'bar'],
            new InMemoryContainer([]),
        );
        self::assertFalse($helpers->isEmpty());
    }
}
