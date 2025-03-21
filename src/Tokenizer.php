<?php

declare(strict_types=1);

namespace Mustache;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\SyntaxException;

use function array_key_exists;
use function array_unshift;
use function assert;
use function is_string;
use function preg_match;
use function sprintf;
use function strlen;
use function strpos;
use function substr;
use function trim;

/**
 * Mustache Tokenizer class.
 *
 * This class is responsible for turning raw template source into a set of Mustache tokens.
 *
 * @psalm-type TokenShape = array{
 *     type: self::T_*,
 *     line: int<0, max>,
 *     name?: string,
 *     dynamic?: bool,
 *     otag?: string,
 *     ctag?: string,
 *     index?: int,
 *     end?: int,
 *     indent?: string,
 *     nodes?: list<array>,
 *     value?: string,
 *     filters?: list<string>,
 * }
 */
final class Tokenizer
{
    // Finite state machine states
    private const IN_TEXT = 0;
    private const IN_TAG_TYPE = 1;
    private const IN_TAG = 2;

    // Token types
    public const T_SECTION = '#';
    public const T_INVERTED = '^';
    public const T_END_SECTION = '/';
    public const T_COMMENT = '!';
    public const T_PARTIAL = '>';
    public const T_PARENT = '<';
    public const T_DELIM_CHANGE = '=';
    public const T_ESCAPED = '_v';
    public const T_UNESCAPED = '{';
    public const T_UNESCAPED_2 = '&';
    public const T_TEXT = '_t';
    public const T_PRAGMA = '%';
    public const T_BLOCK_VAR = '$';
    public const T_BLOCK_ARG = '$arg';
    /**
     * Valid token types
     *
     * @var array<self::T_*, self::T_*>
     */
    private static array $tagTypes = [
        self::T_SECTION => self::T_SECTION,
        self::T_INVERTED => self::T_INVERTED,
        self::T_END_SECTION => self::T_END_SECTION,
        self::T_COMMENT => self::T_COMMENT,
        self::T_PARTIAL => self::T_PARTIAL,
        self::T_PARENT => self::T_PARENT,
        self::T_DELIM_CHANGE => self::T_DELIM_CHANGE,
        self::T_ESCAPED => self::T_ESCAPED,
        self::T_UNESCAPED => self::T_UNESCAPED,
        self::T_UNESCAPED_2 => self::T_UNESCAPED_2,
        self::T_PRAGMA => self::T_PRAGMA,
        self::T_BLOCK_VAR => self::T_BLOCK_VAR,
    ];
    /** @var array<self::T_*, string> */
    private static array $tagNames = [
        self::T_SECTION => 'section',
        self::T_INVERTED => 'inverted section',
        self::T_END_SECTION => 'section end',
        self::T_COMMENT => 'comment',
        self::T_PARTIAL => 'partial',
        self::T_PARENT => 'parent',
        self::T_DELIM_CHANGE => 'set delimiter',
        self::T_ESCAPED => 'variable',
        self::T_UNESCAPED => 'unescaped variable',
        self::T_UNESCAPED_2 => 'unescaped variable',
        self::T_PRAGMA => 'pragma',
        self::T_BLOCK_VAR => 'block variable',
        self::T_BLOCK_ARG => 'block variable',
    ];
    // Token properties
    public const TYPE = 'type';
    public const NAME = 'name';
    public const DYNAMIC = 'dynamic';
    public const OTAG = 'otag';
    public const CTAG = 'ctag';
    public const LINE = 'line';
    public const INDEX = 'index';
    public const END = 'end';
    public const INDENT = 'indent';
    public const NODES = 'nodes';
    public const VALUE = 'value';
    public const FILTERS = 'filters';

    private int $state = self::IN_TEXT;
    /** @var self::T_*|null */
    private string|null $tagType = null;
    private string $buffer = '';
    /** @var list<TokenShape> */
    private array $tokens = [];
    private int $seenTag = 0;
    /** @var int<0, max> */
    private int $line = 0;
    private string $otag = '{{';
    private string $otagChar = '{';
    private int $otagLen = 2;
    private string $ctag = '}}';
    private string $ctagChar = '}';
    private int $ctagLen = 2;

    /**
     * Scan and tokenize template source.
     *
     * @param string $text       Mustache template source to tokenize
     * @param non-empty-string|null $delimiters Optionally, pass initial opening and closing delimiters
     *
     * @return list<TokenShape> Set of Mustache tokens
     *
     * @throws InvalidArgumentException when $delimiters string is invalid.
     * @throws SyntaxException when mismatched section tags are encountered.
     */
    public function scan(string $text, string|null $delimiters = null): array
    {
        $this->reset();

        $delimiters = is_string($delimiters) ? trim($delimiters) : null;

        if ($delimiters !== null && $delimiters !== '') {
            $this->setDelimiters($delimiters);
        }

        $len = strlen($text);
        for ($i = 0; $i < $len; $i++) {
            switch ($this->state) {
                case self::IN_TEXT:
                    $char = $text[$i];
                    // Test whether it's time to change tags.
                    if ($char === $this->otagChar && substr($text, $i, $this->otagLen) === $this->otag) {
                        $i--;
                        $this->flushBuffer();
                        $this->state = self::IN_TAG_TYPE;
                    } else {
                        $this->buffer .= $char;
                        if ($char === "\n") {
                            $this->flushBuffer();
                            $this->line++;
                        }
                    }

                    break;

                case self::IN_TAG_TYPE:
                    $i += $this->otagLen - 1;
                    $char = $text[$i + 1];
                    if (isset(self::$tagTypes[$char])) {
                        $tag = self::$tagTypes[$char];
                        $this->tagType = $tag;
                    } else {
                        $tag = null;
                        $this->tagType = self::T_ESCAPED;
                    }

                    if ($this->tagType === self::T_DELIM_CHANGE) {
                        $i = $this->changeDelimiters($text, $i);
                        $this->state = self::IN_TEXT;
                    } elseif ($this->tagType === self::T_PRAGMA) {
                        $i = $this->addPragma($text, $i);
                        $this->state = self::IN_TEXT;
                    } else {
                        if ($tag !== null) {
                            $i++;
                        }

                        $this->state = self::IN_TAG;
                    }

                    $this->seenTag = $i;
                    break;

                default:
                    $char = $text[$i];
                    // Test whether it's time to change tags.
                    if ($char === $this->ctagChar && substr($text, $i, $this->ctagLen) === $this->ctag) {
                        assert($this->tagType !== null);
                        $token = [
                            self::TYPE => $this->tagType,
                            self::NAME => trim($this->buffer),
                            self::OTAG => $this->otag,
                            self::CTAG => $this->ctag,
                            self::LINE => $this->line,
                            self::INDEX => $this->tagType === self::T_END_SECTION
                                ? $this->seenTag - $this->otagLen
                                : $i + $this->ctagLen,
                        ];

                        if ($this->tagType === self::T_UNESCAPED) {
                            // Clean up `{{{ tripleStache }}}` style tokens.
                            if ($this->ctag === '}}') {
                                if (($i + 2 >= $len) || $text[$i + 2] !== '}') {
                                    $msg = sprintf(
                                        'Mismatched tag delimiters: %s on line %d',
                                        $token[self::NAME],
                                        $token[self::LINE],
                                    );

                                    throw new SyntaxException($msg, $token);
                                }

                                $i++;
                            } else {
                                $lastName = $token[self::NAME];
                                if (substr($lastName, -1) !== '}') {
                                    $msg = sprintf(
                                        'Mismatched tag delimiters: %s on line %d',
                                        $token[self::NAME],
                                        $token[self::LINE],
                                    );

                                    throw new SyntaxException($msg, $token);
                                }

                                $token[self::NAME] = trim(substr($lastName, 0, -1));
                            }
                        }

                        $this->buffer = '';
                        $i += $this->ctagLen - 1;
                        $this->state = self::IN_TEXT;
                        $this->tokens[] = $token;
                    } else {
                        $this->buffer .= $char;
                    }

                    break;
            }
        }

        if ($this->state !== self::IN_TEXT) {
            $this->throwUnclosedTagException();
        }

        $this->flushBuffer();

        return $this->tokens;
    }

    /**
     * Helper function to reset tokenizer internal state.
     */
    private function reset(): void
    {
        $this->state = self::IN_TEXT;
        $this->tagType = null;
        $this->buffer = '';
        $this->tokens = [];
        $this->seenTag = 0;
        $this->line = 0;

        $this->otag = '{{';
        $this->otagChar = '{';
        $this->otagLen = 2;

        $this->ctag = '}}';
        $this->ctagChar = '}';
        $this->ctagLen = 2;
    }

    /**
     * Flush the current buffer to a token.
     */
    private function flushBuffer(): void
    {
        if (strlen($this->buffer) <= 0) {
            return;
        }

        $this->tokens[] = [
            self::TYPE => self::T_TEXT,
            self::LINE => $this->line,
            self::VALUE => $this->buffer,
        ];
        $this->buffer = '';
    }

    /**
     * Change the current Mustache delimiters. Set new `otag` and `ctag` values.
     *
     * @param string $text  Mustache template source
     * @param int $index Current tokenizer index
     *
     * @return int New index value
     *
     * @throws SyntaxException when delimiter string is invalid.
     */
    private function changeDelimiters(string $text, int $index): int
    {
        $strpos = strpos($text, '=', $index);
        assert($strpos !== false);
        $startIndex = $strpos + 1;

        $close = '=' . $this->ctag;
        $closeIndex = strpos($text, $close, $index);

        if ($closeIndex === false) {
            $this->throwUnclosedTagException();
        }

        $token = [
            self::TYPE => self::T_DELIM_CHANGE,
            self::LINE => $this->line,
        ];

        $delimiters = trim(substr($text, $startIndex, $closeIndex - $startIndex));
        if ($delimiters === '') {
            throw new SyntaxException('Empty delimiters', $token);
        }

        try {
            $this->setDelimiters($delimiters);
        } catch (InvalidArgumentException $e) {
            throw new SyntaxException($e->getMessage(), $token);
        }

        $this->tokens[] = $token;

        return $closeIndex + strlen($close) - 1;
    }

    /**
     * Set the current Mustache `otag` and `ctag` delimiters
     *
     * @param non-empty-string $delimiters
     *
     * @throws InvalidArgumentException when delimiter string is invalid.
     */
    private function setDelimiters(string $delimiters): void
    {
        if (! preg_match('/^\s*(\S+)\s+(\S+)\s*$/', $delimiters, $matches)) {
            throw new InvalidArgumentException(sprintf('Invalid delimiters: %s', $delimiters));
        }

        [1 => $otag, 2 => $ctag] = $matches;

        $this->otag = $otag;
        $this->otagChar = $otag[0];
        $this->otagLen = strlen($otag);

        $this->ctag = $ctag;
        $this->ctagChar = $ctag[0];
        $this->ctagLen = strlen($ctag);
    }

    /**
     * Add pragma token.
     *
     * Pragmas are hoisted to the front of the template, so all pragma tokens
     * will appear at the front of the token list.
     *
     * @return int New index value
     */
    private function addPragma(string $text, int $index): int
    {
        $end = strpos($text, $this->ctag, $index);
        if ($end === false) {
            $this->throwUnclosedTagException();
        }

        $pragma = trim(substr($text, $index + 2, $end - $index - 2));
        $token = [
            self::TYPE => self::T_PRAGMA,
            self::NAME => $pragma,
            self::LINE => 0,
        ];
        self::assertPragma($pragma, $token);

        // Pragmas are hoisted to the front of the template.
        array_unshift($this->tokens, $token);

        return $end + $this->ctagLen - 1;
    }

    /**
     * @internal
     *
     * @param TokenShape $token
     *
     * @return Engine::PRAGMA_*
     *
     * @throws SyntaxException
     */
    public static function assertPragma(mixed $value, array $token): string
    {
        if (is_string($value) && array_key_exists($value, Engine::KNOWN_PRAGMAS)) {
            return $value;
        }

        throw new SyntaxException(sprintf(
            'Invalid pragma "%s"',
            (string) $value,
        ), $token);
    }

    /** @throws SyntaxException */
    private function throwUnclosedTagException(): never
    {
        $name = trim($this->buffer);
        if ($name !== '') {
            $msg = sprintf('Unclosed tag: %s on line %d', $name, $this->line);
        } else {
            $msg = sprintf('Unclosed tag on line %d', $this->line);
        }

        throw new SyntaxException($msg, [
            self::TYPE => $this->tagType,
            self::NAME => $name,
            self::OTAG => $this->otag,
            self::CTAG => $this->ctag,
            self::LINE => $this->line,
            self::INDEX => $this->seenTag - $this->otagLen,
        ]);
    }

    /**
     * Get the human-readable name for a tag type.
     *
     * @param self::T_* $tagType One of the tokenizer T_* constants
     */
    public static function getTagName(string $tagType): string
    {
        return self::$tagNames[$tagType] ?? 'unknown';
    }
}
