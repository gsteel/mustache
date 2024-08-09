<?php

declare(strict_types=1);

namespace Mustache\Test\ContextTestAsset;

final class Dummy
{
    public string $name = 'dummy';

    public function __invoke(): void
    {
        // nothing
    }

    public static function foo(): string
    {
        return '<foo>';
    }

    public function bar(): string
    {
        return '<bar>';
    }
}
