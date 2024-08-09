<?php

namespace Mustache\Test;

use Mustache\Exception\SyntaxException;
use Mustache\Tokenizer;
use PHPUnit\Framework\TestCase;

class TokenizerTest extends TestCase
{
    /**
     * @dataProvider getTokens
     */
    public function testScan($text, $delimiters, $expected)
    {
        $tokenizer = new Tokenizer();
        $this->assertSame($expected, $tokenizer->scan($text, $delimiters));
    }

    public function testUnevenBracesThrowExceptions()
    {
        $tokenizer = new Tokenizer();
        $text = '{{{ name }}';
        $this->expectException(SyntaxException::class);
        $tokenizer->scan($text, null);
    }

    public function testUnevenBracesWithCustomDelimiterThrowExceptions()
    {
        $tokenizer = new Tokenizer();
        $text = '<%{ name %>';
        $this->expectException(SyntaxException::class);
        $tokenizer->scan($text, '<% %>');
    }

    public function getTokens()
    {
        return array(
            array(
                'text',
                null,
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'text',
                    ),
                ),
            ),

            array(
                'text',
                '<<< >>>',
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'text',
                    ),
                ),
            ),

            array(
                '{{ name }}',
                null,
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'name',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 10,
                    ),
                ),
            ),

            array(
                '{{ name }}',
                '<<< >>>',
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '{{ name }}',
                    ),
                ),
            ),

            array(
                '<<< name >>>',
                '<<< >>>',
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'name',
                        Tokenizer::OTAG  => '<<<',
                        Tokenizer::CTAG  => '>>>',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 12,
                    ),
                ),
            ),

            array(
                "{{{ a }}}\n{{# b }}  \n{{= | | =}}| c ||/ b |\n|{ d }|",
                null,
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_UNESCAPED,
                        Tokenizer::NAME  => 'a',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => "\n",
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'b',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 1,
                        Tokenizer::INDEX => 18,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 1,
                        Tokenizer::VALUE => "  \n",
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_DELIM_CHANGE,
                        Tokenizer::LINE  => 2,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'c',
                        Tokenizer::OTAG  => '|',
                        Tokenizer::CTAG  => '|',
                        Tokenizer::LINE  => 2,
                        Tokenizer::INDEX => 37,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'b',
                        Tokenizer::OTAG  => '|',
                        Tokenizer::CTAG  => '|',
                        Tokenizer::LINE  => 2,
                        Tokenizer::INDEX => 37,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 2,
                        Tokenizer::VALUE => "\n",
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_UNESCAPED,
                        Tokenizer::NAME  => 'd',
                        Tokenizer::OTAG  => '|',
                        Tokenizer::CTAG  => '|',
                        Tokenizer::LINE  => 3,
                        Tokenizer::INDEX => 51,
                    ),

                ),
            ),

            // See https://github.com/bobthecow/mustache.php/issues/183
            array(
                '{{# a }}0{{/ a }}',
                null,
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_SECTION,
                        Tokenizer::NAME  => 'a',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '0',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'a',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 9,
                    ),
                ),
            ),

            // custom delimiters don't swallow the next character, even if it is a }, }}}, or the same delimiter
            array(
                '<% a %>} <% b %>%> <% c %>}}}',
                '<% %>',
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'a',
                        Tokenizer::OTAG  => '<%',
                        Tokenizer::CTAG  => '%>',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 7,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '} ',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'b',
                        Tokenizer::OTAG  => '<%',
                        Tokenizer::CTAG  => '%>',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 16,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '%> ',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'c',
                        Tokenizer::OTAG  => '<%',
                        Tokenizer::CTAG  => '%>',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 26,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => '}}}',
                    ),
                ),
            ),

            // unescaped custom delimiters are properly parsed
            array(
                '<%{ a }%>',
                '<% %>',
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_UNESCAPED,
                        Tokenizer::NAME  => 'a',
                        Tokenizer::OTAG  => '<%',
                        Tokenizer::CTAG  => '%>',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 9,
                    ),
                ),
            ),

            // Ensure that $arg token is not picked up during tokenization
            array(
                '{{$arg}}default{{/arg}}',
                null,
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_BLOCK_VAR,
                        Tokenizer::NAME  => 'arg',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 8,
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_TEXT,
                        Tokenizer::LINE  => 0,
                        Tokenizer::VALUE => 'default',
                    ),
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_END_SECTION,
                        Tokenizer::NAME  => 'arg',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 15,
                    ),
                ),
            ),

            // Delimiters are trimmed
            array(
                '<% name %>',
                ' <% %> ',
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'name',
                        Tokenizer::OTAG  => '<%',
                        Tokenizer::CTAG  => '%>',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 10,
                    ),
                ),
            ),

            // An empty string makes delimiters fall back to default
            array(
                '{{ name }}',
                '',
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'name',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 10,
                    ),
                ),
            ),

            // A bad delimiter type makes delimiters fall back to default
            array(
                '{{ name }}',
                42,
                array(
                    array(
                        Tokenizer::TYPE  => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME  => 'name',
                        Tokenizer::OTAG  => '{{',
                        Tokenizer::CTAG  => '}}',
                        Tokenizer::LINE  => 0,
                        Tokenizer::INDEX => 10,
                    ),
                ),
            ),
        );
    }

    /**
     * @dataProvider getUnclosedTags
     */
    public function testUnclosedTagsThrowExceptions($text)
    {
        $tokenizer = new Tokenizer();
        $this->expectException(SyntaxException::class);
        $tokenizer->scan($text, null);
    }

    public function getUnclosedTags()
    {
        return [
            ['{{ name'],
            ['{{ name }'],
            ['{{{ name'],
            ['{{{ name }'],
            ['{{& name'],
            ['{{& name }'],
            ['{{# name'],
            ['{{# name }'],
            ['{{^ name'],
            ['{{^ name }'],
            ['{{/ name'],
            ['{{/ name }'],
            ['{{> name'],
            ['{{< name'],
            ['{{> name }'],
            ['{{< name }'],
            ['{{$ name'],
            ['{{$ name }'],
            ['{{= <% %>'],
            ['{{= <% %>='],
            ['{{= <% %>=}'],
            ['{{% name'],
            ['{{% name }'],
        ];
    }
}
