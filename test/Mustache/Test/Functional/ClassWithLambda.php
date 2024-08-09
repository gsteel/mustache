<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Closure;

use function strtoupper;

final class ClassWithLambda
{
    public function upper(): Closure
    {
        return static function (string $val): string {
            return strtoupper($val);
        };
    }

    public function placeholder(): Closure
    {
        return static function (): string {
            return 'Enter your name';
        };
    }
}
