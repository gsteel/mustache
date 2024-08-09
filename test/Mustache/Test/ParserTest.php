<?php

declare(strict_types=1);

namespace Mustache\Test;

use Mustache\Engine;
use Mustache\Exception\SyntaxException;
use Mustache\Parser;
use Mustache\Tokenizer;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $tokens
     * @param list<array<string, mixed>> $expected
     *
     * @dataProvider getTokenSets
     */
    public function testParse(array $tokens, array $expected): void
    {
        $parser = new Parser();
        $this->assertEquals($expected, $parser->parse($tokens));
    }

    /** @return list<array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}> */
    public static function getTokenSets(): array
    {
        return [
            [
                [],
                [],
            ],

            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'text',
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'text',
                    ],
                ],
            ],

            [
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::LINE => 0,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::LINE => 0,
                        Tokenizer::NAME => 'name',
                    ],
                ],
            ],

            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'foo',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                        Tokenizer::NAME  => 'parent',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::LINE  => 0,
                        Tokenizer::NAME  => 'name',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 456,
                        Tokenizer::NAME  => 'parent',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                ],

                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'foo',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                        Tokenizer::END   => 456,
                        Tokenizer::NODES => [
                            [
                                Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                                Tokenizer::LINE => 0,
                                Tokenizer::NAME => 'name',
                            ],
                        ],
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                ],
            ],

            // This *would* be an invalid inheritance parse tree, but that pragma
            // isn't enabled so it'll thunk it back into an "escaped" token:
            [
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => '$foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                ],
            ],

            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '  ',
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_DELIM_CHANGE,
                        Tokenizer::LINE => 0,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => "  \n",
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '[[',
                        Tokenizer::CTAG => ']]',
                        Tokenizer::LINE => 1,
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '[[',
                        Tokenizer::CTAG => ']]',
                        Tokenizer::LINE => 1,
                    ],
                ],
            ],

        ];
    }

    /**
     * @param list<array<string, mixed>> $tokens
     * @param list<array<string, mixed>> $expected
     *
     * @dataProvider getInheritanceTokenSets
     */
    public function testParseWithInheritance(array $tokens, array $expected): void
    {
        $parser = new Parser();
        $parser->setPragmas([Engine::PRAGMA_BLOCKS]);
        $this->assertEquals($expected, $parser->parse($tokens));
    }

    /** @return list<array{0: list<array<string, mixed>>, 1: list<array<string, mixed>>}> */
    public static function getInheritanceTokenSets(): array
    {
        return [
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_PARENT,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME  => 'bar',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 16,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'baz',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'bar',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 19,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 27,
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_PARENT,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                        Tokenizer::END   => 27,
                        Tokenizer::NODES => [
                            [
                                Tokenizer::TYPE  => Tokenizer::T_BLOCK_ARG,
                                Tokenizer::NAME  => 'bar',
                                Tokenizer::OTAG  => '{{',
                                Tokenizer::CTAG  => '}}',
                                Tokenizer::LINE  => 0,
                                Tokenizer::INDEX => 16,
                                Tokenizer::END   => 19,
                                Tokenizer::NODES => [
                                    [
                                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                                        Tokenizer::LINE  => 0,
                                        Tokenizer::VALUE => 'baz',
                                    ],
                                ],
                            ],
                        ],
                    ],
                ],
            ],

            [
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 11,
                    ],
                ],
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::END   => 11,
                        Tokenizer::NODES => [
                            [
                                Tokenizer::TYPE  => Tokenizer::T_TEXT,
                                Tokenizer::LINE  => 0,
                                Tokenizer::VALUE => 'bar',
                            ],
                        ],
                    ],
                ],
            ],
        ];
    }

    /**
     * @param list<array<string, mixed>> $tokens
     *
     * @dataProvider getBadParseTrees
     */
    public function testParserThrowsExceptions(array $tokens): void
    {
        $parser = new Parser();
        $this->expectException(SyntaxException::class);
        $parser->parse($tokens);
    }

    /** @return list<array{0: list<array<string, mixed>>}> */
    public static function getBadParseTrees(): array
    {
        return [
            // no close
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],

            // no close inverted
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],

            // no opening inverted
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],

            // weird nesting
            [
                [
                    [
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'child',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'child',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ],
                ],
            ],

            // This *would* be a valid inheritance parse tree, but that pragma
            // isn't enabled here so it's going to fail :)
            [
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ],
                    [
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 11,
                    ],
                ],
            ],
        ];
    }
}
