<?php

namespace Mustache\Test;

use Mustache\HelperCollection;
use PHPUnit\Framework\TestCase;

class HelperCollectionTest extends TestCase
{
    public function testConstructor()
    {
        $foo = array($this, 'getFoo');
        $bar = 'BAR';

        $helpers = new HelperCollection(array(
            'foo' => $foo,
            'bar' => $bar,
        ));

        $this->assertSame($foo, $helpers->get('foo'));
        $this->assertSame($bar, $helpers->get('bar'));
    }

    public static function getFoo()
    {
        echo 'foo';
    }

    public function testAccessorsAndMutators()
    {
        $foo = array($this, 'getFoo');
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

    public function testMagicMethods()
    {
        $foo = array($this, 'getFoo');
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
     * @dataProvider getInvalidHelperArguments
     */
    public function testHelperCollectionIsntAfraidToThrowExceptions($helpers = [], $actions = [], $exception = null)
    {
        if ($exception) {
            $this->expectException($exception);
        } else {
            $this->expectNotToPerformAssertions();
        }

        $helpers = new HelperCollection($helpers);

        foreach ($actions as $method => $args) {
            call_user_func_array(array($helpers, $method), $args);
        }
    }

    public function getInvalidHelperArguments(): array
    {
        return [
            [
                'not helpers',
                [],
                'Mustache\Exception\InvalidArgumentException',
            ],
            [
                [],
                ['get' => ['foo']],
                'Mustache\Exception\InvalidArgumentException',
            ],
            [
                ['foo' => 'FOO'],
                ['get' => ['foo']],
                null,
            ],
            [
                ['foo' => 'FOO'],
                ['get' => ['bar']],
                'Mustache\Exception\InvalidArgumentException',
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
                'Mustache\Exception\InvalidArgumentException',
            ],
            [
                [],
                ['remove' => ['foo']],
                'Mustache\Exception\InvalidArgumentException',
            ],
        ];
    }
}
