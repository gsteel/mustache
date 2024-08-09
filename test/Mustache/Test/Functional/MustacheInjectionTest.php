<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group mustache_injection
 * @group functional
 */
class MustacheInjectionTest extends TestCase
{
    private Engine $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    /**
     * @param array<string, mixed> $data
     * @param array<string, string> $partials
     *
     * @dataProvider injectionData
     */
    public function testInjection(string $tpl, array $data, array $partials, string $expect): void
    {
        $this->mustache->setPartials($partials);
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    /** @return list<array{1: string, 2: array<string, mixed>, 3: array<string, string>, 4: string}> */
    public static function injectionData(): array
    {
        $interpolationData = [
            'a' => '{{ b }}',
            'b' => 'FAIL',
        ];

        $sectionData = [
            'a' => true,
            'b' => '{{ c }}',
            'c' => 'FAIL',
        ];

        $lambdaInterpolationData = [
            'a' => [self::class, 'lambdaInterpolationCallback'],
            'b' => '{{ c }}',
            'c' => 'FAIL',
        ];

        $lambdaSectionData = [
            'a' => [self::class, 'lambdaSectionCallback'],
            'b' => '{{ c }}',
            'c' => 'FAIL',
        ];

        return [
            ['{{ a }}',   $interpolationData, [], '{{ b }}'],
            ['{{{ a }}}', $interpolationData, [], '{{ b }}'],

            ['{{# a }}{{ b }}{{/ a }}',   $sectionData, [], '{{ c }}'],
            ['{{# a }}{{{ b }}}{{/ a }}', $sectionData, [], '{{ c }}'],

            ['{{> partial }}', $interpolationData, ['partial' => '{{ a }}'],   '{{ b }}'],
            ['{{> partial }}', $interpolationData, ['partial' => '{{{ a }}}'], '{{ b }}'],

            ['{{ a }}',           $lambdaInterpolationData, [], '{{ c }}'],
            ['{{# a }}b{{/ a }}', $lambdaSectionData,       [], '{{ c }}'],
        ];
    }

    public static function lambdaInterpolationCallback(): string
    {
        return '{{ b }}';
    }

    public static function lambdaSectionCallback(string $text): string
    {
        return '{{ ' . $text . ' }}';
    }
}
