<?php

declare(strict_types=1);

namespace Mustache\Test;

use Mustache\Compiler;
use Mustache\Exception\SyntaxException;
use Mustache\Tokenizer;
use PHPUnit\Framework\TestCase;

use const ENT_COMPAT;
use const ENT_QUOTES;

class CompilerTest extends TestCase
{
    /**
     * @param list<array<string, mixed>> $tree
     * @param list<string> $expected
     *
     * @dataProvider getCompileValues
     */
    public function testCompile(
        string $source,
        array $tree,
        string $name,
        bool $customEscaper,
        int $entityFlags,
        string $charset,
        array $expected,
    ): void {
        $compiler = new Compiler();

        $compiled = $compiler->compile(
            $source,
            $tree,
            $name,
            $customEscaper,
            $charset,
            false,
            $entityFlags,
        );
        foreach ($expected as $contains) {
            $this->assertStringContainsString($contains, $compiled);
        }
    }

    /**
     * phpcs:disable Generic.Files.LineLength
     *
     * @return list<array{
     *     0: string,
     *     1: list<array<string, mixed>>,
     *     2: string,
     *     3: bool,
     *     4: int,
     *     5: string,
     *     6: list<string>,
     * }>
     */
    public static function getCompileValues(): array
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

            [
                '',
                [self::createTextToken('TEXT')],
                'Monkey',
                false,
                ENT_COMPAT,
                'UTF-8',
                [
                    "\nclass Monkey extends \Mustache\Template",
                    '$buffer .= $indent . \'TEXT\';',
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
                    self::createTextToken("foo\n"),
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => 'name',
                    ],
                    [
                        Tokenizer::TYPE => Tokenizer::T_ESCAPED,
                        Tokenizer::NAME => '.',
                    ],
                    self::createTextToken("'bar'"),
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

    public function testCompilerThrowsSyntaxException(): void
    {
        $compiler = new Compiler();
        $this->expectException(SyntaxException::class);
        $compiler->compile('', [[Tokenizer::TYPE => 'invalid']], 'SomeClass');
    }

    /** @return array<string, mixed> */
    private static function createTextToken(string $value): array
    {
        return [
            Tokenizer::TYPE  => Tokenizer::T_TEXT,
            Tokenizer::VALUE => $value,
        ];
    }
}
