<?php

declare(strict_types=1);

namespace Mustache\Test;

use Mustache\Exception\InvalidArgumentException;
use Mustache\HelperCollection;
use PHPUnit\Framework\TestCase;
use Throwable;

use function call_user_func_array;

class HelperCollectionTest extends TestCase
{
    public function testConstructor(): void
    {
        $foo = [$this, 'getFoo'];
        $bar = 'BAR';

        $helpers = new HelperCollection([
            'foo' => $foo,
            'bar' => $bar,
        ]);

        $this->assertSame($foo, $helpers->get('foo'));
        $this->assertSame($bar, $helpers->get('bar'));
    }

    public static function getFoo(): void
    {
        echo 'foo';
    }

    public function testAccessorsAndMutators(): void
    {
        $foo = [$this, 'getFoo'];
        $bar = 'BAR';

        $helpers = new HelperCollection();
        $this->assertTrue($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));

        $helpers->add('foo', $foo);
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));

        $helpers->add('bar', $bar);
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));

        $helpers->remove('foo');
        $this->assertFalse($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
    }

    public function testMagicMethods(): void
    {
        $foo = [$this, 'getFoo'];
        $bar = 'BAR';

        $helpers = new HelperCollection();
        $this->assertTrue($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));
        $this->assertFalse(isset($helpers->foo));
        $this->assertFalse(isset($helpers->bar));

        $helpers->foo = $foo;
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertFalse($helpers->has('bar'));
        $this->assertTrue(isset($helpers->foo));
        $this->assertFalse(isset($helpers->bar));

        $helpers->bar = $bar;
        $this->assertFalse($helpers->isEmpty());
        $this->assertTrue($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
        $this->assertTrue(isset($helpers->foo));
        $this->assertTrue(isset($helpers->bar));

        unset($helpers->foo);
        $this->assertFalse($helpers->isEmpty());
        $this->assertFalse($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
        $this->assertFalse(isset($helpers->foo));
        $this->assertTrue(isset($helpers->bar));
    }

    /**
     * @param iterable<string, mixed> $helpers
     * @param array<array-key, mixed> $actions
     * @param class-string<Throwable>|null $exception
     *
     * @dataProvider getInvalidHelperArguments
     */
    public function testHelperCollectionIsntAfraidToThrowExceptions(
        iterable $helpers = [],
        array $actions = [],
        string|null $exception = null,
    ): void {
        if ($exception !== null) {
            $this->expectException($exception);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $helpers = new HelperCollection($helpers);

        foreach ($actions as $method => $args) {
            call_user_func_array([$helpers, $method], $args);
        }
    }

    /**
     * @return list<array{
     *     0: iterable<string, mixed>,
     *     1: array<array-key, mixed>,
     *     2: class-string<Throwable>|null,
     * }>
     */
    public static function getInvalidHelperArguments(): array
    {
        return [
            [
                [],
                ['get' => ['foo']],
                InvalidArgumentException::class,
            ],
            [
                ['foo' => 'FOO'],
                ['get' => ['foo']],
                null,
            ],
            [
                ['foo' => 'FOO'],
                ['get' => ['bar']],
                InvalidArgumentException::class,
            ],
            [
                ['foo' => 'FOO'],
                [
                    'add' => ['bar', 'BAR'],
                    'get' => ['bar'],
                ],
                null,
            ],
            [
                ['foo' => 'FOO'],
                [
                    'get'    => ['foo'],
                    'remove' => ['foo'],
                ],
                null,
            ],
            [
                ['foo' => 'FOO'],
                [
                    'remove' => ['foo'],
                    'get'    => ['foo'],
                ],
                InvalidArgumentException::class,
            ],
            [
                [],
                ['remove' => ['foo']],
                InvalidArgumentException::class,
            ],
        ];
    }
}
