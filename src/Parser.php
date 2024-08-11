<?php

declare(strict_types=1);

namespace Mustache;

use Mustache\Exception\SyntaxException;

use function array_map;
use function array_pop;
use function array_shift;
use function count;
use function end;
use function explode;
use function is_array;
use function preg_match;
use function preg_replace;
use function reset;
use function sprintf;
use function substr;

/**
 * Mustache Parser class.
 *
 * This class is responsible for turning a set of Mustache tokens into a parse tree.
 */
final class Parser
{
    private int $lineNum = -1;
    private int $lineTokens = 0;
    /** @var array<Engine::PRAGMA_*, bool> */
    private array $pragmas = [];
    /** @var array<Engine::PRAGMA_*, bool> */
    private array $defaultPragmas = [];
    private bool $pragmaFilters = false;
    private bool $pragmaBlocks = false;
    private bool $pragmaDynamicNames = false;

    /**
     * Process an array of Mustache tokens and convert them into a parse tree.
     *
     * @param list<array<string, mixed>> $tokens Set of Mustache tokens
     *
     * @return list<array<string, mixed>> Mustache token parse tree
     */
    public function parse(array $tokens = []): array
    {
        $this->lineNum = -1;
        $this->lineTokens = 0;
        $this->pragmas = $this->defaultPragmas;

        $this->pragmaFilters = isset($this->pragmas[Engine::PRAGMA_FILTERS]);
        $this->pragmaBlocks = isset($this->pragmas[Engine::PRAGMA_BLOCKS]);
        $this->pragmaDynamicNames = isset($this->pragmas[Engine::PRAGMA_DYNAMIC_NAMES]);

        return $this->buildTree($tokens);
    }

    /**
     * Enable pragmas across all templates, regardless of the presence of pragma
     * tags in the individual templates.
     *
     * @internal Users should set global pragmas in Mustache\Engine, not here :)
     *
     * @param list<Engine::PRAGMA_*> $pragmas
     */
    public function setPragmas(array $pragmas): void
    {
        $this->pragmas = [];
        foreach ($pragmas as $pragma) {
            $this->enablePragma($pragma);
        }

        $this->defaultPragmas = $this->pragmas;
    }

    /**
     * Helper method for recursively building a parse tree.
     *
     * @param list<array<string, mixed>> &$tokens Set of Mustache tokens
     * @param list<array<string, mixed>>|null $parent  Parent token (default: null)
     *
     * @return list<array<string, mixed>> Mustache Token parse tree
     *
     * @throws SyntaxException when nesting errors or mismatched section tags are encountered.
     */
    private function buildTree(array &$tokens, array|null $parent = null): array
    {
        $nodes = [];

        while (! empty($tokens)) {
            $token = array_shift($tokens);

            if ($token[Tokenizer::LINE] === $this->lineNum) {
                $this->lineTokens++;
            } else {
                $this->lineNum = $token[Tokenizer::LINE];
                $this->lineTokens = 0;
            }

            if ($token[Tokenizer::TYPE] !== Tokenizer::T_COMMENT) {
                if ($this->pragmaDynamicNames && isset($token[Tokenizer::NAME])) {
                    [$name, $isDynamic] = $this->getDynamicName($token);
                    if ($isDynamic) {
                        $token[Tokenizer::NAME] = $name;
                        $token[Tokenizer::DYNAMIC] = true;
                    }
                }

                if ($this->pragmaFilters && isset($token[Tokenizer::NAME])) {
                    [$name, $filters] = $this->getNameAndFilters($token[Tokenizer::NAME]);
                    if (! empty($filters)) {
                        $token[Tokenizer::NAME] = $name;
                        $token[Tokenizer::FILTERS] = $filters;
                    }
                }
            }

            switch ($token[Tokenizer::TYPE]) {
                case Tokenizer::T_DELIM_CHANGE:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                    $this->clearStandaloneLines($nodes, $tokens);
                    break;

                case Tokenizer::T_SECTION:
                case Tokenizer::T_INVERTED:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                    $this->clearStandaloneLines($nodes, $tokens);
                    $nodes[] = $this->buildTree($tokens, $token);
                    break;

                case Tokenizer::T_END_SECTION:
                    if (! isset($parent)) {
                        $msg = sprintf(
                            'Unexpected closing tag: /%s on line %d',
                            $token[Tokenizer::NAME],
                            $token[Tokenizer::LINE],
                        );

                        throw new SyntaxException($msg, $token);
                    }

                    $sameName = $token[Tokenizer::NAME] !== $parent[Tokenizer::NAME];
                    $tokenDynamic = isset($token[Tokenizer::DYNAMIC]) && $token[Tokenizer::DYNAMIC];
                    $parentDynamic = isset($parent[Tokenizer::DYNAMIC]) && $parent[Tokenizer::DYNAMIC];

                    if ($sameName || ($tokenDynamic !== $parentDynamic)) {
                        $msg = sprintf(
                            'Nesting error: %s%s (on line %d) vs. %s%s (on line %d)',
                            $parentDynamic ? '*' : '',
                            $parent[Tokenizer::NAME],
                            $parent[Tokenizer::LINE],
                            $tokenDynamic ? '*' : '',
                            $token[Tokenizer::NAME],
                            $token[Tokenizer::LINE],
                        );

                        throw new SyntaxException($msg, $token);
                    }

                    $this->clearStandaloneLines($nodes, $tokens);
                    $parent[Tokenizer::END] = $token[Tokenizer::INDEX];
                    $parent[Tokenizer::NODES] = $nodes;

                    return $parent;

                case Tokenizer::T_PARTIAL:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                    //store the whitespace prefix for laters!
                    $indent = $this->clearStandaloneLines($nodes, $tokens);
                    if ($indent !== null) {
                        $token[Tokenizer::INDENT] = $indent[Tokenizer::VALUE];
                    }

                    $nodes[] = $token;
                    break;

                case Tokenizer::T_PARENT:
                    $this->checkIfTokenIsAllowedInParent($parent, $token);
                    $nodes[] = $this->buildTree($tokens, $token);
                    break;

                case Tokenizer::T_BLOCK_VAR:
                    if ($this->pragmaBlocks) {
                        // BLOCKS pragma is enabled, let's do this!
                        if (isset($parent) && $parent[Tokenizer::TYPE] === Tokenizer::T_PARENT) {
                            $token[Tokenizer::TYPE] = Tokenizer::T_BLOCK_ARG;
                        }

                        $this->clearStandaloneLines($nodes, $tokens);
                        $nodes[] = $this->buildTree($tokens, $token);
                    } else {
                        // pretend this was just a normal "escaped" token...
                        $token[Tokenizer::TYPE] = Tokenizer::T_ESCAPED;
                        // TODO: figure out how to figure out if there was a space after this dollar:
                        $token[Tokenizer::NAME] = '$' . $token[Tokenizer::NAME];
                        $nodes[] = $token;
                    }

                    break;

                case Tokenizer::T_PRAGMA:
                    $this->enablePragma($token[Tokenizer::NAME]);
                // no break

                case Tokenizer::T_COMMENT:
                    $this->clearStandaloneLines($nodes, $tokens);
                    $nodes[] = $token;
                    break;

                default:
                    $nodes[] = $token;
                    break;
            }
        }

        if (isset($parent)) {
            $msg = sprintf(
                'Missing closing tag: %s opened on line %d',
                $parent[Tokenizer::NAME],
                $parent[Tokenizer::LINE],
            );

            throw new SyntaxException($msg, $parent);
        }

        return $nodes;
    }

    /**
     * Clear standalone line tokens.
     *
     * Returns a whitespace token for indenting partials, if applicable.
     *
     * @param list<array<string, mixed>> &$nodes  Parsed nodes
     * @param list<array<string, mixed>> &$tokens Tokens to be parsed
     *
     * @return array<string, mixed>|null Resulting indent token, if any
     */
    private function clearStandaloneLines(array &$nodes, array &$tokens): array|null
    {
        if ($this->lineTokens > 1) {
            // this is the third or later node on this line, so it can't be standalone
            return null;
        }

        $prev = null;
        if ($this->lineTokens === 1) {
            // this is the second node on this line, so it can't be standalone
            // unless the previous node is whitespace.
            $prev = end($nodes);
            if ($prev !== false) {
                if (! $this->tokenIsWhitespace($prev)) {
                    return null;
                }
            }
        }

        $next = reset($tokens);
        if ($next !== false) {
            // If we're on a new line, bail.
            if ($next[Tokenizer::LINE] !== $this->lineNum) {
                return null;
            }

            // If the next token isn't whitespace, bail.
            if (! $this->tokenIsWhitespace($next)) {
                return null;
            }

            if (count($tokens) !== 1) {
                // Unless it's the last token in the template, the next token
                // must end in newline for this to be standalone.
                if (substr($next[Tokenizer::VALUE], -1) !== "\n") {
                    return null;
                }
            }

            // Discard the whitespace suffix
            array_shift($tokens);
        }

        if (is_array($prev)) {
            // Return the whitespace prefix, if any
            return array_pop($nodes);
        }

        return null;
    }

    /**
     * Check whether token is a whitespace token.
     *
     * True if token type is T_TEXT and value is all whitespace characters.
     *
     * @param array<string, mixed> $token
     *
     * @return bool True if token is a whitespace token
     */
    private function tokenIsWhitespace(array $token): bool
    {
        if ($token[Tokenizer::TYPE] === Tokenizer::T_TEXT) {
            return (bool) preg_match('/^\s*$/', $token[Tokenizer::VALUE]);
        }

        return false;
    }

    /**
     * Check whether a token is allowed inside a parent tag.
     *
     * @param array<string, mixed>|null $parent
     * @param array<string, mixed> $token
     *
     * @throws SyntaxException if an invalid token is found inside a parent tag.
     */
    private function checkIfTokenIsAllowedInParent(array|null $parent, array $token): void
    {
        if (isset($parent) && $parent[Tokenizer::TYPE] === Tokenizer::T_PARENT) {
            throw new SyntaxException('Illegal content in < parent tag', $token);
        }
    }

    /**
     * Parse dynamic names.
     *
     * @param array<string, mixed> $token
     *
     * @return array{0: string, 1: bool}
     *
     * @throws SyntaxException when a tag does not allow *.
     * @throws SyntaxException on multiple *s, or dots or filters with *.
     */
    private function getDynamicName(array $token): array
    {
        $name = $token[Tokenizer::NAME];
        $isDynamic = false;

        if (preg_match('/^\s*\*\s*/', $name)) {
            $this->ensureTagAllowsDynamicNames($token);
            $name = preg_replace('/^\s*\*\s*/', '', $name);
            $isDynamic = true;
        }

        return [$name, $isDynamic];
    }

    /**
     * Check whether the given token supports dynamic tag names.
     *
     * @param array<string, mixed> $token
     *
     * @throws SyntaxException when a tag does not allow *.
     */
    private function ensureTagAllowsDynamicNames(array $token): void
    {
        switch ($token[Tokenizer::TYPE]) {
            case Tokenizer::T_PARTIAL:
            case Tokenizer::T_PARENT:
            case Tokenizer::T_END_SECTION:
                return;
        }

        $msg = sprintf(
            'Invalid dynamic name: %s in %s tag',
            $token[Tokenizer::NAME],
            Tokenizer::getTagName($token[Tokenizer::TYPE]),
        );

        throw new SyntaxException($msg, $token);
    }

    /**
     * Split a tag name into name and filters.
     *
     * @return array{0: string, 1: list<string>} [Tag name, Array of filters]
     */
    private function getNameAndFilters(string $name): array
    {
        $filters = array_map('trim', explode('|', $name));
        $name = array_shift($filters);

        return [$name, $filters];
    }

    /**
     * Enable a pragma.
     *
     * @param Engine::PRAGMA_* $name
     */
    private function enablePragma(string $name): void
    {
        $this->pragmas[$name] = true;

        switch ($name) {
            case Engine::PRAGMA_BLOCKS:
                $this->pragmaBlocks = true;
                break;

            case Engine::PRAGMA_FILTERS:
                $this->pragmaFilters = true;
                break;

            case Engine::PRAGMA_DYNAMIC_NAMES:
                $this->pragmaDynamicNames = true;
                break;
        }
    }
}
