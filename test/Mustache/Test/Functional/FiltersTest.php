<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Closure;
use DateTime;
use DateTimeZone;
use Mustache\Engine;
use Mustache\Exception\UnknownFilterException;
use PHPUnit\Framework\TestCase;
use stdClass;

use function array_keys;
use function array_map;
use function sprintf;

/**
 * @group filters
 * @group functional
 */
class FiltersTest extends TestCase
{
    private const CHAINED_SECTION_FILTERS_TPL = <<<'EOS'
        {{% FILTERS }}
        {{# word | echo | with_index }}
        {{ key }}: {{ value }}
        {{/ word | echo | with_index }}
        EOS;

    private Engine $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    /**
     * @param array<array-key, mixed>|object $data
     * @param array<string, Closure> $helpers
     *
     * @dataProvider singleFilterData
     */
    public function testSingleFilter(string $tpl, array $helpers, $data, string $expect): void
    {
        $this->mustache->setHelpers($helpers);
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    /** @return list<array{0: string, 1: array<string, Closure>, 2: object|array, 3: string}> */
    public static function singleFilterData(): array
    {
        $helpers = [
            'longdate' => static function (DateTime $value) {
                return $value->format('Y-m-d h:m:s');
            },
            'echo' => static function ($value) {
                return [$value, $value, $value];
            },
        ];

        return [
            [
                '{{% FILTERS }}{{ date | longdate }}',
                $helpers,
                (object) ['date' => new DateTime('1/1/2000', new DateTimeZone('UTC'))],
                '2000-01-01 12:01:00',
            ],

            [
                '{{% FILTERS }}{{# word | echo }}{{ . }}!{{/ word | echo }}',
                $helpers,
                ['word' => 'bacon'],
                'bacon!bacon!bacon!',
            ],
        ];
    }

    public function testChainedFilters(): void
    {
        $tpl = $this->mustache->loadTemplate('{{% FILTERS }}{{ date | longdate | withbrackets }}');

        $this->mustache->addHelper('longdate', static function (DateTime $value) {
            return $value->format('Y-m-d h:m:s');
        });

        $this->mustache->addHelper('withbrackets', static function ($value) {
            return sprintf('[[%s]]', $value);
        });

        $foo = new stdClass();
        $foo->date = new DateTime('1/1/2000', new DateTimeZone('UTC'));

        $this->assertEquals('[[2000-01-01 12:01:00]]', $tpl->render($foo));
    }

    public function testChainedSectionFilters(): void
    {
        $tpl = $this->mustache->loadTemplate(self::CHAINED_SECTION_FILTERS_TPL);

        $this->mustache->addHelper('echo', static function ($value) {
            return [$value, $value, $value];
        });

        $this->mustache->addHelper('with_index', static function ($value) {
            return array_map(static function ($k, $v) {
                return [
                    'key'   => $k,
                    'value' => $v,
                ];
            }, array_keys($value), $value);
        });

        $this->assertEquals("0: bacon\n1: bacon\n2: bacon\n", $tpl->render(['word' => 'bacon']));
    }

    /**
     * @param array<string, mixed> $data
     *
     * @dataProvider interpolateFirstData
     */
    public function testInterpolateFirst(string $tpl, array $data, string $expect): void
    {
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    /** @return list<array{0: string, 1: array<string, mixed>, 2: string}> */
    public static function interpolateFirstData(): array
    {
        $data = [
            'foo' => 'FOO',
            'bar' => static function ($value) {
                return $value === 'FOO' ? 'win!' : 'fail :(';
            },
        ];

        return [
            ['{{% FILTERS }}{{ foo | bar }}',                         $data, 'win!'],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}', $data, 'win!'],
        ];
    }

    /**
     * @param array<string, mixed> $data
     *
     * @dataProvider brokenPipeData
     */
    public function testThrowsExceptionForBrokenPipes(string $tpl, array $data): void
    {
        $this->expectException(UnknownFilterException::class);
        $this->mustache->render($tpl, $data);
    }

    /** @return list<array{0: string, 1: array<string, mixed>}> */
    public static function brokenPipeData(): array
    {
        return [
            ['{{% FILTERS }}{{ foo | bar }}',       []],
            ['{{% FILTERS }}{{ foo | bar }}',       ['foo' => 'FOO']],
            ['{{% FILTERS }}{{ foo | bar }}',       ['foo' => 'FOO', 'bar' => 'BAR']],
            ['{{% FILTERS }}{{ foo | bar }}',       ['foo' => 'FOO', 'bar' => [1, 2]]],
            [
                '{{% FILTERS }}{{ foo | bar | baz }}',
                [
                    'foo' => 'FOO',
                    'bar' => static function () {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar | baz }}',
                [
                    'foo' => 'FOO',
                    'baz' => static function () {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar | baz }}',
                [
                    'bar' => static function () {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar | baz }}',
                [
                    'baz' => static function () {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar.baz }}',
                [
                    'foo' => 'FOO',
                    'bar' => static function () {
                        return 'BAR';
                    },
                    'baz' => static function () {
                        return 'BAZ';
                    },
                ],
            ],

            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             []],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             ['foo' => 'FOO']],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             ['foo' => 'FOO', 'bar' => 'BAR']],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}',             ['foo' => 'FOO', 'bar' => [1, 2]]],
            [
                '{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}',
                [
                    'foo' => 'FOO',
                    'bar' => static function () {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}',
                [
                    'foo' => 'FOO',
                    'baz' => static function () {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}',
                [
                    'bar' => static function () {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}',
                [
                    'baz' => static function () {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar.baz }}{{ . }}{{/ foo | bar.baz }}',
                [
                    'foo' => 'FOO',
                    'bar' => static function () {
                        return 'BAR';
                    },
                    'baz' => static function () {
                        return 'BAZ';
                    },
                ],
            ],
        ];
    }
}
