<?php

namespace Mustache\Test;

use Mustache\Engine;
use Mustache\Exception\SyntaxException;
use Mustache\Parser;
use Mustache\Tokenizer;
use PHPUnit\Framework\TestCase;

class ParserTest extends TestCase
{
    /**
     * @dataProvider getTokenSets
     */
    public function testParse($tokens, $expected)
    {
        $parser = new Parser();
        $this->assertEquals($expected, $parser->parse($tokens));
    }

    public function getTokenSets()
    {
        return array(
            array(
                array(),
                array(),
            ),

            array(
                array(array(
                    Tokenizer::TYPE  => Tokenizer::T_TEXT,
                    Tokenizer::LINE  => 0,
                    Tokenizer::VALUE => 'text',
                )),
                array(array(
                    Tokenizer::TYPE  => Tokenizer::T_TEXT,
                    Tokenizer::LINE  => 0,
                    Tokenizer::VALUE => 'text',
                )),
            ),

            array(
                array(array(
                    Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                    Tokenizer::LINE => 0,
                    Tokenizer::NAME => 'name',
                )),
                array(array(
                    Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                    Tokenizer::LINE => 0,
                    Tokenizer::NAME => 'name',
                )),
            ),

            array(
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'foo',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                        Tokenizer::NAME  => 'parent',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::LINE  => 0,
                        Tokenizer::NAME  => 'name',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 456,
                        Tokenizer::NAME  => 'parent',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ),
                ),

                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'foo',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                        Tokenizer::END   => 456,
                        Tokenizer::NODES => array(
                            array(
                                Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                                Tokenizer::LINE => 0,
                                Tokenizer::NAME => 'name',
                            ),
                        ),
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ),
                ),
            ),

            // This *would* be an invalid inheritance parse tree, but that pragma
            // isn't enabled so it'll thunk it back into an "escaped" token:
            array(
                array(
                    array(
                        Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ),
                ),
                array(
                    array(
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => '$foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ),
                ),
            ),

            array(
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '  ',
                    ),
                    array(
                        Tokenizer::TYPE => Tokenizer::T_DELIM_CHANGE,
                        Tokenizer::LINE => 0,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => "  \n",
                    ),
                    array(
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '[[',
                        Tokenizer::CTAG => ']]',
                        Tokenizer::LINE => 1,
                    ),
                ),
                array(
                    array(
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '[[',
                        Tokenizer::CTAG => ']]',
                        Tokenizer::LINE => 1,
                    ),
                ),
            ),

        );
    }

    /**
     * @dataProvider getInheritanceTokenSets
     */
    public function testParseWithInheritance($tokens, $expected)
    {
        $parser = new Parser();
        $parser->setPragmas(array(Engine::PRAGMA_BLOCKS));
        $this->assertEquals($expected, $parser->parse($tokens));
    }

    public function getInheritanceTokenSets()
    {
        return array(
            array(
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_PARENT,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME  => 'bar',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 16,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'baz',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'bar',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 19,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 27,
                    ),
                ),
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_PARENT,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                        Tokenizer::END   => 27,
                        Tokenizer::NODES => array(
                            array(
                                Tokenizer::TYPE  => Tokenizer::T_BLOCK_ARG,
                                Tokenizer::NAME  => 'bar',
                                Tokenizer::OTAG  => '{{',
                                Tokenizer::CTAG  => '}}',
                                Tokenizer::LINE  => 0,
                                Tokenizer::INDEX => 16,
                                Tokenizer::END   => 19,
                                Tokenizer::NODES => array(
                                    array(
                                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                                        Tokenizer::LINE  => 0,
                                        Tokenizer::VALUE => 'baz',
                                    ),
                                ),
                            ),
                        ),
                    ),
                ),
            ),

            array(
                array(
                    array(
                        Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 11,
                    ),
                ),
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::END   => 11,
                        Tokenizer::NODES => array(
                            array(
                                Tokenizer::TYPE  => Tokenizer::T_TEXT,
                                Tokenizer::LINE  => 0,
                                Tokenizer::VALUE => 'bar',
                            ),
                        ),
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider getBadParseTrees
     */
    public function testParserThrowsExceptions($tokens)
    {
        $parser = new Parser();
        $this->expectException(SyntaxException::class);
        $parser->parse($tokens);
    }

    public function getBadParseTrees()
    {
        return array(
            // no close
            array(
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // no close inverted
            array(
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_INVERTED,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // no opening inverted
            array(
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // weird nesting
            array(
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'child',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'parent',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'child',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 123,
                    ),
                ),
            ),

            // This *would* be a valid inheritance parse tree, but that pragma
            // isn't enabled here so it's going to fail :)
            array(
                array(
                    array(
                        Tokenizer::TYPE => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME => 'foo',
                        Tokenizer::OTAG => '{{',
                        Tokenizer::CTAG => '}}',
                        Tokenizer::LINE => 0,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'bar',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'foo',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 11,
                    ),
                ),
            ),
        );
    }
}
