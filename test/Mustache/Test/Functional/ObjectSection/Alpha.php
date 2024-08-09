<?php

declare(strict_types=1);

namespace Mustache\Test\Functional\ObjectSection;

use stdClass;

final class Alpha
{
    public object $foo;

    public function __construct()
    {
        $this->foo = new stdClass();
        $this->foo->name = 'Foo';
        $this->foo->number = 1;
    }
}
