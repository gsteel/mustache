<?php

declare(strict_types=1);

namespace Mustache\Test\Functional\ObjectSection;

final class Delta
{
    private string $name = 'Foo';

    public function name(): string
    {
        return $this->name;
    }
}
