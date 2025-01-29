<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('magic_methods')]
#[Group('functional')]
class CallTest extends TestCase
{
    public function testCallEatsContext(): void
    {
        $m = new Engine();
        $tpl = $m->loadTemplate('{{# foo }}{{ label }}: {{ name }}{{/ foo }}');

        $foo = new ClassWithCall();
        $foo->name = 'Bob';

        $data = ['label' => 'name', 'foo' => $foo];

        $this->assertEquals('name: Bob', $tpl->render($data));
    }
}
