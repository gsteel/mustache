<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 * @group partials
 */
class NestedPartialIndentTest extends TestCase
{
    /**
     * @param array<string, string> $partials
     *
     * @dataProvider partialsAndStuff
     */
    public function testNestedPartialsAreIndentedProperly(string $src, array $partials, string $expected): void
    {
        $m = new Engine([
            'partials' => $partials,
        ]);
        $tpl = $m->loadTemplate($src);
        $this->assertEquals($expected, $tpl->render());
    }

    /** @return list<array{0: string, 1: array<string, string>, 2: string}> */
    public static function partialsAndStuff(): array
    {
        $partials = [
            'a' => ' {{> b }}',
            'b' => ' {{> d }}',
            'c' => ' {{> d }}{{> d }}',
            'd' => 'D!',
        ];

        return [
            [' {{> a }}', $partials, '   D!'],
            [' {{> b }}', $partials, '  D!'],
            [' {{> c }}', $partials, '  D!D!'],
        ];
    }
}
