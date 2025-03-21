<?php

declare(strict_types=1);

namespace Mustache\Test\Helper;

use Mustache\Exception\UnknownHelperException;
use Mustache\Helper\CascadingHelperManager;
use Mustache\Helper\ImmutableHelperManager;
use Override;
use PHPUnit\Framework\TestCase;

final class CascadingHelperManagerTest extends TestCase
{
    private CascadingHelperManager $helpers;

    #[Override]
    protected function setUp(): void
    {
        $this->helpers = new CascadingHelperManager([
            new ImmutableHelperManager([
                'foo' => 'bar',
            ]),
            new ImmutableHelperManager([
                'bar' => 'baz',
            ]),
        ]);
    }

    public function testHas(): void
    {
        self::assertTrue($this->helpers->has('foo'));
        self::assertTrue(isset($this->helpers->foo));

        self::assertFalse($this->helpers->has('baz'));
        self::assertFalse(isset($this->helpers->baz));
    }

    public function testGet(): void
    {
        self::assertSame('bar', $this->helpers->get('foo'));
        self::assertSame('bar', $this->helpers->__get('foo'));
        self::assertSame('baz', $this->helpers->get('bar'));
        self::assertSame('baz', $this->helpers->__get('bar'));
    }

    public function testExceptionThrownAccessingNonExistentHelper(): void
    {
        $this->expectException(UnknownHelperException::class);
        $this->helpers->__get('Muppets');
    }

    public function testIsEmpty(): void
    {
        $helpers = new CascadingHelperManager([]);
        self::assertTrue($helpers->isEmpty());

        self::assertFalse($this->helpers->isEmpty());
    }
}
