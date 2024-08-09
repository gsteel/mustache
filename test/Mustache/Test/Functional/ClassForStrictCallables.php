<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\LambdaHelper;

use function strtoupper;

final class ClassForStrictCallables
{
    public function instanceCallable(string $tpl, LambdaHelper $mustache): string
    {
        return strtoupper($mustache->render($tpl));
    }

    public static function staticCallable(string $tpl, LambdaHelper $mustache): string
    {
        return strtoupper($mustache->render($tpl));
    }

    public function instanceName(): string
    {
        return 'Yoshi';
    }

    public static function staticName(): string
    {
        return 'Yoshi';
    }
}
