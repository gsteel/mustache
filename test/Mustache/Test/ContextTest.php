<?php

declare(strict_types=1);

namespace Mustache\Test;

use Mustache\Context;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Test\ContextTestAsset\AllTheThings;
use Mustache\Test\ContextTestAsset\ArrayAccessImplementor;
use Mustache\Test\ContextTestAsset\Dummy;
use PHPUnit\Framework\TestCase;
use stdClass;

class ContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $one = new Context();
        $this->assertSame('', $one->find('foo'));
        $this->assertSame('', $one->find('bar'));

        $two = new Context([
            'foo' => 'FOO',
            'bar' => '<BAR>',
        ]);
        $this->assertEquals('FOO', $two->find('foo'));
        $this->assertEquals('<BAR>', $two->find('bar'));

        $obj = new stdClass();
        $obj->name = 'NAME';
        $three = new Context($obj);
        $this->assertSame($obj, $three->last());
        $this->assertEquals('NAME', $three->find('name'));
    }

    public function testPushPopAndLast(): void
    {
        $context = new Context();
        $this->assertFalse($context->last());

        $dummy = new Dummy();
        $context->push($dummy);
        $this->assertSame($dummy, $context->last());
        $this->assertSame($dummy, $context->pop());
        $this->assertFalse($context->last());

        $obj = new stdClass();
        $context->push($dummy);
        $this->assertSame($dummy, $context->last());
        $context->push($obj);
        $this->assertSame($obj, $context->last());
        $this->assertSame($obj, $context->pop());
        $this->assertSame($dummy, $context->pop());
        $this->assertFalse($context->last());
    }

    public function testFind(): void
    {
        $context = new Context();

        $dummy = new Dummy();

        $obj = new stdClass();
        $obj->name = 'obj';

        $arr = [
            'a' => ['b' => ['c' => 'see']],
            'b' => 'bee',
        ];

        $string = 'some arbitrary string';

        $context->push($dummy);
        $this->assertEquals('dummy', $context->find('name'));

        $context->push($obj);
        $this->assertEquals('obj', $context->find('name'));

        $context->pop();
        $this->assertEquals('dummy', $context->find('name'));

        $dummy->name = 'dummyer';
        $this->assertEquals('dummyer', $context->find('name'));

        $context->push($arr);
        $this->assertEquals('bee', $context->find('b'));
        $this->assertEquals('see', $context->findDot('a.b.c'));

        $dummy->name = 'dummy';

        $context->push($string);
        $this->assertSame($string, $context->last());
        $this->assertEquals('dummy', $context->find('name'));
        $this->assertEquals('see', $context->findDot('a.b.c'));
        $this->assertEquals('<foo>', $context->find('foo'));
        $this->assertEquals('<bar>', $context->findDot('bar'));
    }

    public function testArrayAccessFind(): void
    {
        $access = new ArrayAccessImplementor([
            'a' => ['b' => ['c' => 'see']],
            'b' => 'bee',
        ]);

        $context = new Context($access);
        $this->assertEquals('bee', $context->find('b'));
        $this->assertEquals('see', $context->findDot('a.b.c'));
        $this->assertEquals(null, $context->findDot('a.b.c.d'));
    }

    public function testAccessorPriority(): void
    {
        $context = new Context(new AllTheThings());

        $this->assertEquals('win', $context->find('foo'), 'method beats property');
        $this->assertEquals('win', $context->find('bar'), 'property beats ArrayAccess');
        $this->assertEquals('win', $context->find('baz'), 'ArrayAccess stands alone');
        $this->assertEquals('win', $context->find('qux'), 'ArrayAccess beats private property');
    }

    public function testAnchoredDotNotation(): void
    {
        $context = new Context();

        $a = [
            'name'   => 'a',
            'number' => 1,
        ];

        $b = [
            'number' => 2,
            'child'  => [
                'name' => 'baby bee',
            ],
        ];

        $c = [
            'name' => 'cee',
        ];

        $context->push($a);
        $this->assertEquals('a', $context->find('name'));
        $this->assertEquals('', $context->findDot('.name'));
        $this->assertEquals('a', $context->findAnchoredDot('.name'));
        $this->assertEquals(1, $context->find('number'));
        $this->assertEquals('', $context->findDot('.number'));
        $this->assertEquals(1, $context->findAnchoredDot('.number'));

        $context->push($b);
        $this->assertEquals('a', $context->find('name'));
        $this->assertEquals(2, $context->find('number'));
        $this->assertEquals('', $context->findDot('.name'));
        $this->assertEquals('', $context->findDot('.number'));
        $this->assertEquals('', $context->findAnchoredDot('.name'));
        $this->assertEquals(2, $context->findAnchoredDot('.number'));
        $this->assertEquals('baby bee', $context->findDot('child.name'));
        $this->assertEquals('', $context->findDot('.child.name'));
        $this->assertEquals('baby bee', $context->findAnchoredDot('.child.name'));

        $context->push($c);
        $this->assertEquals('cee', $context->find('name'));
        $this->assertEquals('', $context->findDot('.name'));
        $this->assertEquals('cee', $context->findAnchoredDot('.name'));
        $this->assertEquals(2, $context->find('number'));
        $this->assertEquals('', $context->findDot('.number'));
        $this->assertEquals('', $context->findAnchoredDot('.number'));
        $this->assertEquals('baby bee', $context->findDot('child.name'));
        $this->assertEquals('', $context->findDot('.child.name'));
        $this->assertEquals('', $context->findAnchoredDot('.child.name'));
    }

    public function testAnchoredDotNotationThrowsExceptions(): void
    {
        $context = new Context();
        $context->push(['a' => 1]);
        $this->expectException(InvalidArgumentException::class);
        $context->findAnchoredDot('a');
    }

    public function testNullArrayValueMasking(): void
    {
        $context = new Context();

        $a = [
            'name' => 'not null',
        ];
        $b = [
            'name' => null,
        ];

        $context->push($a);
        $context->push($b);

        $this->assertNull($context->find('name'));
    }

    public function testNullPropertyValueMasking(): void
    {
        $context = new Context();

        $a = (object) [
            'name' => 'not null',
        ];
        $b = (object) [
            'name' => null,
        ];

        $context->push($a);
        $context->push($b);

        $this->assertNull($context->find('name'));
    }

    public function testBuggyNullPropertyValueMasking(): void
    {
        $context = new Context(null, true);

        $a = (object) [
            'name' => 'not null',
        ];
        $b = (object) [
            'name' => null,
        ];

        $context->push($a);
        $context->push($b);

        $this->assertEquals($context->find('name'), 'not null');
    }
}
