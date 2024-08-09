<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\LambdaHelper;
use PHPUnit\Framework\TestCase;
use stdClass;

use function strtoupper;

/**
 * @group lambdas
 * @group functional
 */
class StrictCallablesTest extends TestCase
{
    /**
     * @param mixed $name
     *
     * @dataProvider callables
     */
    public function testStrictCallables(bool $strict, $name, callable $section, string $expected): void
    {
        $mustache = new Engine(['strict_callables' => $strict]);
        $tpl      = $mustache->loadTemplate('{{# section }}{{ name }}{{/ section }}');

        $data = new stdClass();
        $data->name    = $name;
        $data->section = $section;

        $this->assertEquals($expected, $tpl->render($data));
    }

    /** @return list<array{0: bool, 1: mixed, 2: callable, 3: string}> */
    public static function callables(): array
    {
        $lambda = static function (string $tpl, LambdaHelper $mustache) {
            return strtoupper($mustache->render($tpl));
        };

        $instance = new ClassForStrictCallables();

        return [
            // Interpolation lambdas
            [
                false,
                [$instance, 'instanceName'],
                $lambda,
                'YOSHI',
            ],
            [
                false,
                [ClassForStrictCallables::class, 'staticName'],
                $lambda,
                'YOSHI',
            ],
            [
                false,
                static function () {
                    return 'Yoshi';
                },
                $lambda,
                'YOSHI',
            ],

            // Section lambdas
            [
                false,
                'Yoshi',
                [$instance, 'instanceCallable'],
                'YOSHI',
            ],
            [
                false,
                'Yoshi',
                [ClassForStrictCallables::class, 'staticCallable'],
                'YOSHI',
            ],
            [
                false,
                'Yoshi',
                $lambda,
                'YOSHI',
            ],

            // Strict interpolation lambdas
            [
                true,
                static function () {
                    return 'Yoshi';
                },
                $lambda,
                'YOSHI',
            ],

            // Strict section lambdas
            [
                true,
                'Yoshi',
                [$instance, 'instanceCallable'],
                'YoshiYoshi',
            ],
            [
                true,
                'Yoshi',
                [ClassForStrictCallables::class, 'staticCallable'],
                'YoshiYoshi',
            ],
            [
                true,
                'Yoshi',
                static function (string $tpl, LambdaHelper $mustache): string {
                    return strtoupper($mustache->render($tpl));
                },
                'YOSHI',
            ],
        ];
    }
}
