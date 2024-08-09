<?php

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group magic_methods
 * @group functional
 */
class CallTest extends TestCase
{
    public function testCallEatsContext()
    {
        $m = new Engine();
        $tpl = $m->loadTemplate('{{# foo }}{{ label }}: {{ name }}{{/ foo }}');

        $foo = new ClassWithCall();
        $foo->name = 'Bob';

        $data = ['label' => 'name', 'foo' => $foo];

        $this->assertEquals('name: Bob', $tpl->render($data));
    }
}
