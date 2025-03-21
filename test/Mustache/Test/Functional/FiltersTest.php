<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Closure;
use DateTime;
use DateTimeImmutable;
use Mustache\Engine;
use Mustache\Exception\UnknownFilterException;
use Mustache\Helper\ImmutableHelperCollection;
use Override;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

use function array_keys;
use function array_map;
use function sprintf;

#[Group('filters')]
#[Group('functional')]
final class FiltersTest extends TestCase
{
    private Engine $mustache;

    #[Override]
    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    /**
     * @param array<array-key, mixed>|object $data
     * @param array<string, Closure> $helpers
     */
    #[DataProvider('singleFilterData')]
    public function testSingleFilter(string $tpl, array $helpers, array|object $data, string $expect): void
    {
        $engine = new Engine([
            'helpers' => $helpers,
        ]);
        $this->assertEquals($expect, $engine->render($tpl, $data));
    }

    /** @return list<array{0: string, 1: array<string, Closure>, 2: object|array, 3: string}> */
    public static function singleFilterData(): array
    {
        $helpers = [
            'longdate' => static function (DateTimeImmutable $value): string {
                return $value->format('Y-m-d H:i:s');
            },
            'echo' => static function (mixed $value): array {
                return [$value, $value, $value];
            },
        ];

        return [
            [
                '{{% FILTERS }}{{ date | longdate }}',
                $helpers,
                (object) ['date' => DateTimeImmutable::createFromFormat('!Y-m-d', '2020-01-01')],
                '2020-01-01 00:00:00',
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
        $helpers = new ImmutableHelperCollection([
            'longdate' => static fn (DateTime $value): string => $value->format('Y-m-d H:i:s'),
            'withbrackets' => static fn (string $value): string => sprintf('[[%s]]', $value),
        ]);

        $engine = new Engine([
            'helpers' => $helpers,
        ]);

        $date = DateTime::createFromFormat('!Y-m-d', '2020-01-01');

        $result = $engine->render(
            '{{% FILTERS }}{{ date | longdate | withbrackets }}',
            [
                'date' => $date,
            ],
        );

        self::assertSame('[[2020-01-01 00:00:00]]', $result);
    }

    public function testChainedSectionFilters(): void
    {
        $template = <<<'EOS'
            {{% FILTERS }}
            {{# word | echo | with_index }}
            {{ key }}: {{ value }}
            {{/ word | echo | with_index }}
            EOS;

        $helpers = new ImmutableHelperCollection([
            'echo' => static fn (string $value): array => [$value, $value, $value],
            'with_index' => static function (array $value): array {
                return array_map(static function (int|string $k, string $v) {
                    return [
                        'key'   => $k,
                        'value' => $v,
                    ];
                }, array_keys($value), $value);
            },
        ]);

        $engine = new Engine([
            'helpers' => $helpers,
        ]);

        $this->assertSame(
            "0: bacon\n1: bacon\n2: bacon\n",
            $engine->render($template, ['word' => 'bacon']),
        );
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('interpolateFirstData')]
    public function testInterpolateFirst(string $tpl, array $data, string $expect): void
    {
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    /** @return list<array{0: string, 1: array<string, mixed>, 2: string}> */
    public static function interpolateFirstData(): array
    {
        $data = [
            'foo' => 'FOO',
            'bar' => static function (mixed $value): string {
                return $value === 'FOO' ? 'win!' : 'fail :(';
            },
        ];

        return [
            ['{{% FILTERS }}{{ foo | bar }}',                         $data, 'win!'],
            ['{{% FILTERS }}{{# foo | bar }}{{ . }}{{/ foo | bar }}', $data, 'win!'],
        ];
    }

    /** @param array<string, mixed> $data */
    #[DataProvider('brokenPipeData')]
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
                    'bar' => static function (): string {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar | baz }}',
                [
                    'foo' => 'FOO',
                    'baz' => static function (): string {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar | baz }}',
                [
                    'bar' => static function (): string {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar | baz }}',
                [
                    'baz' => static function (): string {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{ foo | bar.baz }}',
                [
                    'foo' => 'FOO',
                    'bar' => static function (): string {
                        return 'BAR';
                    },
                    'baz' => static function (): string {
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
                    'bar' => static function (): string {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}',
                [
                    'foo' => 'FOO',
                    'baz' => static function (): string {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}',
                [
                    'bar' => static function (): string {
                        return 'BAR';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar | baz }}{{ . }}{{/ foo | bar | baz }}',
                [
                    'baz' => static function (): string {
                        return 'BAZ';
                    },
                ],
            ],
            [
                '{{% FILTERS }}{{# foo | bar.baz }}{{ . }}{{/ foo | bar.baz }}',
                [
                    'foo' => 'FOO',
                    'bar' => static function (): string {
                        return 'BAR';
                    },
                    'baz' => static function (): string {
                        return 'BAZ';
                    },
                ],
            ],
        ];
    }
}
