<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

final class ClassWithCall
{
    public $name;

    public function __call($method, $args)
    {
        return 'unknown value';
    }
}
