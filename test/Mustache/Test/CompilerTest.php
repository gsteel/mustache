<?php

namespace Mustache\Test;

use Mustache\Compiler;
use Mustache\Exception\SyntaxException;
use Mustache\Tokenizer;
use PHPUnit\Framework\TestCase;

class CompilerTest extends TestCase
{
    /**
     * @dataProvider getCompileValues
     */
    public function testCompile($source, array $tree, $name, $customEscaper, $entityFlags, $charset, $expected)
    {
        $compiler = new Compiler();

        $compiled = $compiler->compile($source, $tree, $name, $customEscaper, $charset, false, $entityFlags);
        foreach ($expected as $contains) {
            $this->assertStringContainsString($contains, $compiled);
        }
    }

    public function getCompileValues()
    {
        return [
            [
                '',
                [],
                'Banana',
                false,
                ENT_COMPAT,
                'ISO-8859-1',
                [
                    "\nclass Banana extends \Mustache\Template",
                    'return $buffer;',
                ],
            ],

            ['', [$this->createTextToken('TEXT')], 'Monkey', false, ENT_COMPAT, 'UTF-8', [
                "\nclass Monkey extends \Mustache\Template",
                '$buffer .= $indent . \'TEXT\';',
                'return $buffer;',
            ]],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                true,
                ENT_COMPAT,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : call_user_func($this->mustache->getEscape(), $value));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                false,
                ENT_COMPAT,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : htmlspecialchars($value, ' . ENT_COMPAT . ', \'ISO-8859-1\'));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                ],
                'Monkey',
                false,
                ENT_QUOTES,
                'ISO-8859-1',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= $indent . ($value === null ? \'\' : htmlspecialchars($value, ' . ENT_QUOTES . ', \'ISO-8859-1\'));',
                    'return $buffer;',
                ],
            ],

            [
                '',
                [
                    $this->createTextToken("foo\n"),
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => '.',
                    ],
                    $this->createTextToken("'bar'"),
                ],
                'Monkey',
                false,
                ENT_COMPAT,
                'UTF-8',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    "\$buffer .= \$indent . 'foo\n';",
                    '$value = $this->resolveValue($context->find(\'name\'), $context);',
                    '$buffer .= ($value === null ? \'\' : htmlspecialchars($value, ' . ENT_COMPAT . ', \'UTF-8\'));',
                    '$value = $this->resolveValue($context->last(), $context);',
                    '$buffer .= \'\\\'bar\\\'\';',
                    'return $buffer;',
                ],
            ],
        ];
    }

    public function testCompilerThrowsSyntaxException()
    {
        $compiler = new Compiler();
        $this->expectException(SyntaxException::class);
        $compiler->compile('', [[Tokenizer::TYPE => 'invalid']], 'SomeClass');
    }

    /**
     * @param string $value
     */
    private function createTextToken($value)
    {
        return [
            Tokenizer::TYPE  => Tokenizer::T_TEXT,
            Tokenizer::VALUE => $value,
        ];
    }
}
