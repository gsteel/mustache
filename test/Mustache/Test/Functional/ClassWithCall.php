<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

final class ClassWithCall
{
    public string|null $name = null;

    /** @param array<string, mixed> $args */
    public function __call(string $method, array $args): string
    {
        return 'unknown value';
    }
}
