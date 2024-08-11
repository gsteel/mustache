<?php

declare(strict_types=1);

namespace Mustache;

use Mustache\Exception\SyntaxException;

use function array_filter;
use function array_shift;
use function implode;
use function md5;
use function preg_replace;
use function sprintf;
use function str_repeat;
use function strpos;
use function substr;
use function trim;
use function ucfirst;
use function var_export;

use const ENT_HTML401;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * Mustache Compiler class.
 *
 * This class is responsible for turning a Mustache token parse tree into normal PHP source code.
 */
final class Compiler
{
    private const PARTIAL_INDENT = ', $indent . %s';
    private const PARTIAL = <<<'PHP'
        /** @var \Mustache\Template $partial */
        if ($partial = $this->mustache->loadPartial(%s)) {
            $buffer .= $partial->renderInternal($context%s);
        }
        PHP;
    private const PARENT = <<<'PHP'
        /** @var \Mustache\Context $context  */
        /** @var \Mustache\Template $parent */
        if ($parent = $this->mustache->loadPartial(%s)) {
            $context->pushBlockContext([%s
            ]);
            $buffer .= $parent->renderInternal($context, $indent);
            $context->popBlockContext();
        }
        PHP;

    private const PARENT_NO_CONTEXT = <<<'PHP'
        if ($parent = $this->mustache->loadPartial(%s)) {
            $buffer .= $parent->renderInternal($context, $indent);
        }
        PHP;
    private const LINE_INDENT = '$indent . ';
    private const SECTION_CALL = <<<'PHP'
        $value = $context->%s(%s);%s
        $buffer .= $this->section%s($context, $indent, $value);
        PHP;

    private const SECTION = <<<'PHP'
        private function section%s(\Mustache\Context $context, $indent, $value)
        {
            /** @var \Mustache\Template $this */
            /** @var \Mustache\Context $context */
            /** @var \Mustache\Engine $engine */
            $engine = $this->mustache;
            $buffer = '';

            if (%s) {
                $source = %s;
                $result = (string) call_user_func($value, $source, %s);
                if (strpos($result, '{{') === false) {
                    $buffer .= $result;
                } else {
                    $buffer .= $engine
                        ->loadLambda($result%s)
                        ->renderInternal($context);
                }
            } elseif (!empty($value)) {
                $values = $this->isIterable($value) ? $value : [$value];
                foreach ($values as $value) {
                    $context->push($value);
                    %s
                    $context->pop();
                }
            }

            return $buffer;
        }
        PHP;
    private const KLASS = <<<'PHP'
        <?php

        class %s extends \Mustache\Template
        {
            private $lambdaHelper;%s

            public function renderInternal(\Mustache\Context $context, string $indent = ''): string
            {
                $this->lambdaHelper = new \Mustache\LambdaHelper($this->mustache, $context);
                $buffer = '';
        %s

                return $buffer;
            }
        %s
        %s
        }
        PHP;

    private const KLASS_NO_LAMBDAS = <<<'PHP'
        <?php

        class %s extends \Mustache\Template
        {%s
            public function renderInternal(\Mustache\Context $context, string $indent = ''): string
            {
                $buffer = '';
        %s

                return $buffer;
            }
        }
        PHP;
    private const STRICT_CALLABLE = 'protected bool $strictCallables = true;';
    private const LINE = '$buffer .= "\n";';
    private const TEXT = '$buffer .= %s%s;';
    private const INVERTED_SECTION = <<<'PHP'
        $value = $context->%s(%s);%s
        if (empty($value)) {
            %s
        }
        PHP;
    private const DYNAMIC_NAME = <<<'PHP'
        /** @var \Mustache\Template $this */
        $this->resolveValue($context->%s(%s), $context)
        PHP;
    private const BLOCK_VAR = <<<'PHP'
        /** @var \Mustache\Context $context */
        $blockFunction = $context->findInBlock(%s);
        if (is_callable($blockFunction)) {
            $buffer .= call_user_func($blockFunction, $context);
        %s}
        PHP;
    private const BLOCK_VAR_ELSE = '} else {%s';
    private const BLOCK_ARG = '%s => [$this, \'block%s\'],';
    private const BLOCK_FUNCTION = <<<'PHP'
        public function block%s($context)
        {
            $indent = $buffer = '';%s

            return $buffer;
        }
        PHP;
    private const VARIABLE = <<<'PHP'
        $value = $this->resolveValue($context->%s(%s), $context);%s
        $buffer .= %s($value === null ? '' : %s);
        PHP;
    private const DEFAULT_ESCAPE = 'htmlspecialchars(%s, %s, %s)';
    private const CUSTOM_ESCAPE = 'call_user_func($this->mustache->getEscape(), %s)';
    private const IS_CALLABLE = '!is_string(%s) && is_callable(%s)';
    private const STRICT_IS_CALLABLE = 'is_object(%s) && is_callable(%s)';

    private const FILTER = <<<'PHP'
        $filter = $context->%s(%s);
        if (! (%s)) {
            throw new \Mustache\Exception\UnknownFilterException(%s);
        }
        $value = call_user_func($filter, $value);%s
        PHP;

    /** @var array<Engine::PRAGMA_*, bool> */
    private array $pragmas = [];
    /** @var array<Engine::PRAGMA_*, bool> */
    private array $defaultPragmas = [];
    /** @var array<string, string> */
    private array $sections = [];
    /** @var array<string, string> */
    private array $blocks = [];
    private string $source = '';
    private bool $indentNextLine = false;
    private bool $customEscape = false;
    private int $entityFlags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401;
    private string $charset = 'utf-8';
    private bool $strictCallables = false;

    /**
     * Compile a Mustache token parse tree into PHP source code.
     *
     * @param string $source          Mustache Template source code
     * @param list<array<string, mixed>> $tree            Parse tree of Mustache tokens
     * @param string $name            Mustache Template class name
     * @param bool $customEscape    (default: false)
     * @param string $charset         (default: 'UTF-8')
     * @param bool $strictCallables (default: false)
     * @param int $entityFlags     (default: ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401)
     *
     * @return string Generated PHP source code
     */
    public function compile(
        string $source,
        array $tree,
        string $name,
        bool $customEscape = false,
        string $charset = 'UTF-8',
        bool $strictCallables = false,
        int $entityFlags = ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401,
    ): string {
        $this->pragmas = $this->defaultPragmas;
        $this->sections = [];
        $this->blocks = [];
        $this->source = $source;
        $this->indentNextLine = true;
        $this->customEscape = $customEscape;
        $this->entityFlags = $entityFlags;
        $this->charset = $charset;
        $this->strictCallables = $strictCallables;

        return $this->writeCode($tree, $name);
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
            $this->pragmas[$pragma] = true;
        }

        $this->defaultPragmas = $this->pragmas;
    }

    /**
     * Helper function for walking the Mustache token parse tree.
     *
     * @param list<array<string, mixed>> $tree  Parse tree of Mustache tokens
     * @param int $level (default: 0)
     *
     * @return string Generated PHP source code
     *
     * @throws SyntaxException upon encountering unknown token types.
     */
    private function walk(array $tree, int $level = 0): string
    {
        $code = '';
        $level++;
        foreach ($tree as $node) {
            switch ($node[Tokenizer::TYPE]) {
                case Tokenizer::T_PRAGMA:
                    $this->pragmas[$node[Tokenizer::NAME]] = true;
                    break;

                case Tokenizer::T_SECTION:
                    $code .= $this->section(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::FILTERS] ?? [],
                        $node[Tokenizer::INDEX],
                        $node[Tokenizer::END],
                        $node[Tokenizer::OTAG],
                        $node[Tokenizer::CTAG],
                        $level,
                    );
                    break;

                case Tokenizer::T_INVERTED:
                    $code .= $this->invertedSection(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::FILTERS] ?? [],
                        $level,
                    );
                    break;

                case Tokenizer::T_PARTIAL:
                    $code .= $this->partial(
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::DYNAMIC] ?? false,
                        $node[Tokenizer::INDENT] ?? '',
                        $level,
                    );
                    break;

                case Tokenizer::T_PARENT:
                    $code .= $this->parent(
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::DYNAMIC] ?? false,
                        $node[Tokenizer::INDENT] ?? '',
                        $node[Tokenizer::NODES],
                        $level,
                    );
                    break;

                case Tokenizer::T_BLOCK_ARG:
                    $code .= $this->blockArg(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::INDEX],
                        $node[Tokenizer::END],
                        $node[Tokenizer::OTAG],
                        $node[Tokenizer::CTAG],
                        $level,
                    );
                    break;

                case Tokenizer::T_BLOCK_VAR:
                    $code .= $this->blockVar(
                        $node[Tokenizer::NODES],
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::INDEX],
                        $node[Tokenizer::END],
                        $node[Tokenizer::OTAG],
                        $node[Tokenizer::CTAG],
                        $level,
                    );
                    break;

                case Tokenizer::T_COMMENT:
                    break;

                case Tokenizer::T_ESCAPED:
                case Tokenizer::T_UNESCAPED:
                case Tokenizer::T_UNESCAPED_2:
                    $code .= $this->variable(
                        $node[Tokenizer::NAME],
                        $node[Tokenizer::FILTERS] ?? [],
                        $node[Tokenizer::TYPE] === Tokenizer::T_ESCAPED,
                        $level,
                    );
                    break;

                case Tokenizer::T_TEXT:
                    $code .= $this->text($node[Tokenizer::VALUE], $level);
                    break;

                default:
                    throw new SyntaxException(sprintf('Unknown token type: %s', $node[Tokenizer::TYPE]), $node);
            }
        }

        return $code;
    }

    /**
     * Generate Mustache Template class PHP source.
     *
     * @param list<array<string, mixed>> $tree Parse tree of Mustache tokens
     * @param string $name Mustache Template class name
     *
     * @return string Generated PHP source code
     */
    private function writeCode(array $tree, string $name): string
    {
        $code = $this->walk($tree);
        $sections = implode("\n", $this->sections);
        $blocks = implode("\n", $this->blocks);
        $klass = empty($this->sections) && empty($this->blocks) ? self::KLASS_NO_LAMBDAS : self::KLASS;

        $callable = $this->strictCallables ? $this->prepare(self::STRICT_CALLABLE) : '';

        return sprintf(
            $this->prepare($klass, 0, false, true),
            $name,
            $callable,
            $code,
            $sections,
            $blocks,
        );
    }

    /**
     * Generate Mustache Template inheritance block variable PHP source.
     *
     * @param list<array<string, mixed>> $nodes Array of child tokens
     * @param string $id    Section name
     * @param int    $start Section start offset
     * @param int    $end   Section end offset
     * @param string $otag  Current Mustache opening tag
     * @param string $ctag  Current Mustache closing tag
     *
     * @return string Generated PHP source code
     */
    private function blockVar(
        array $nodes,
        string $id,
        int $start,
        int $end,
        string $otag,
        string $ctag,
        int $level,
    ): string {
        $id = var_export($id, true);

        $else = $this->walk($nodes, $level);
        if ($else !== '') {
            $else = sprintf(
                $this->prepare(self::BLOCK_VAR_ELSE, $level + 1, false, true),
                $else,
            );
        }

        return sprintf($this->prepare(self::BLOCK_VAR, $level), $id, $else);
    }

    /**
     * Generate Mustache Template inheritance block argument PHP source.
     *
     * @param list<array<string, mixed>> $nodes Array of child tokens
     * @param string $id    Section name
     * @param int    $start Section start offset
     * @param int    $end   Section end offset
     * @param string $otag  Current Mustache opening tag
     * @param string $ctag  Current Mustache closing tag
     *
     * @return string Generated PHP source code
     */
    private function blockArg(
        array $nodes,
        string $id,
        int $start,
        int $end,
        string $otag,
        string $ctag,
        int $level,
    ): string {
        $key = $this->block($nodes);
        $id = var_export($id, true);

        return sprintf($this->prepare(self::BLOCK_ARG, $level), $id, $key);
    }

    /**
     * Generate Mustache Template inheritance block function PHP source.
     *
     * @param list<array<string, mixed>> $nodes Array of child tokens
     *
     * @return string key of new block function
     */
    private function block(array $nodes): string
    {
        $code = $this->walk($nodes, 0);
        $key = ucfirst(md5($code));

        if (! isset($this->blocks[$key])) {
            $this->blocks[$key] = sprintf($this->prepare(self::BLOCK_FUNCTION, 0), $key, $code);
        }

        return $key;
    }

    /**
     * Generate Mustache Template section PHP source.
     *
     * @param list<array<string, mixed>> $nodes   Array of child tokens
     * @param string   $id      Section name
     * @param list<string> $filters Array of filters
     * @param int      $start   Section start offset
     * @param int      $end     Section end offset
     * @param string   $otag    Current Mustache opening tag
     * @param string   $ctag    Current Mustache closing tag
     *
     * @return string Generated section PHP source code
     */
    private function section(
        array $nodes,
        string $id,
        array $filters,
        int $start,
        int $end,
        string $otag,
        string $ctag,
        int $level,
    ): string {
        $source = var_export(substr($this->source, $start, $end - $start), true);
        $callable = $this->getCallable();

        if ($otag !== '{{' || $ctag !== '}}') {
            $delimTag = var_export(sprintf('{{= %s %s =}}', $otag, $ctag), true);
            $helper = sprintf('$this->lambdaHelper->withDelimiters(%s)', $delimTag);
            $delims = ', ' . $delimTag;
        } else {
            $helper = '$this->lambdaHelper';
            $delims = '';
        }

        $key = ucfirst(md5($delims . "\n" . $source));

        if (! isset($this->sections[$key])) {
            $this->sections[$key] = sprintf(
                $this->prepare(self::SECTION),
                $key,
                $callable,
                $source,
                $helper,
                $delims,
                $this->walk($nodes, 2),
            );
        }

        $method = $this->getFindMethod($id);
        $id = var_export($id, true);
        $filters = $this->getFilters($filters, $level);

        return sprintf($this->prepare(self::SECTION_CALL, $level), $method, $id, $filters, $key);
    }

    /**
     * Generate Mustache Template inverted section PHP source.
     *
     * @param list<array<string, mixed>> $nodes   Array of child tokens
     * @param string   $id      Section name
     * @param list<string> $filters Array of filters
     *
     * @return string Generated inverted section PHP source code
     */
    private function invertedSection(array $nodes, string $id, array $filters, int $level): string
    {
        $method = $this->getFindMethod($id);
        $id = var_export($id, true);
        $filters = $this->getFilters($filters, $level);

        return sprintf(
            $this->prepare(self::INVERTED_SECTION, $level),
            $method,
            $id,
            $filters,
            $this->walk($nodes, $level),
        );
    }

    /**
     * Generate Mustache Template dynamic name resolution PHP source.
     *
     * @param string $id      Tag name
     * @param bool   $dynamic True if the name is dynamic
     *
     * @return string Dynamic name resolution PHP source code
     */
    private function resolveDynamicName(string $id, bool $dynamic): string
    {
        if (! $dynamic) {
            return var_export($id, true);
        }

        $method = $this->getFindMethod($id);
        $id = $method !== 'last' ? var_export($id, true) : '';

        // TODO: filters?

        return sprintf(self::DYNAMIC_NAME, $method, $id);
    }

    /**
     * Generate Mustache Template partial call PHP source.
     *
     * @param string $id      Partial name
     * @param bool   $dynamic Partial name is dynamic
     * @param string $indent  Whitespace indent to apply to partial
     *
     * @return string Generated partial call PHP source code
     */
    private function partial(string $id, bool $dynamic, string $indent, int $level): string
    {
        if ($indent !== '') {
            $indentParam = sprintf(self::PARTIAL_INDENT, var_export($indent, true));
        } else {
            $indentParam = '';
        }

        return sprintf(
            $this->prepare(self::PARTIAL, $level),
            $this->resolveDynamicName($id, $dynamic),
            $indentParam,
        );
    }

    /**
     * Generate Mustache Template inheritance parent call PHP source.
     *
     * @param string $id       Parent tag name
     * @param bool   $dynamic  Tag name is dynamic
     * @param string $indent   Whitespace indent to apply to parent
     * @param list<array<string, mixed>> $children Child nodes
     *
     * @return string Generated PHP source code
     */
    private function parent(string $id, bool $dynamic, string $indent, array $children, int $level): string
    {
        $realChildren = array_filter($children, static fn (array $node): bool => self::onlyBlockArgs($node));
        $partialName = $this->resolveDynamicName($id, $dynamic);

        if ($realChildren === []) {
            return sprintf($this->prepare(self::PARENT_NO_CONTEXT, $level), $partialName);
        }

        return sprintf(
            $this->prepare(self::PARENT, $level),
            $partialName,
            $this->walk($realChildren, $level + 1),
        );
    }

    /**
     * Helper method for filtering out non-block-arg tokens.
     *
     * @param array<string, mixed> $node
     *
     * @return bool True if $node is a block arg token
     */
    private static function onlyBlockArgs(array $node): bool
    {
        return $node[Tokenizer::TYPE] === Tokenizer::T_BLOCK_ARG;
    }

    /**
     * Generate Mustache Template variable interpolation PHP source.
     *
     * @param string   $id      Variable name
     * @param string[] $filters Array of filters
     * @param bool     $escape  Escape the variable value for output?
     *
     * @return string Generated variable interpolation PHP source
     */
    private function variable(string $id, array $filters, bool $escape, int $level): string
    {
        $method = $this->getFindMethod($id);
        $id = $method !== 'last' ? var_export($id, true) : '';
        $filters = $this->getFilters($filters, $level);
        $value = $escape ? $this->getEscape() : '$value';

        return sprintf($this->prepare(self::VARIABLE, $level), $method, $id, $filters, $this->flushIndent(), $value);
    }

    /**
     * Generate Mustache Template variable filtering PHP source.
     *
     * @param string[] $filters Array of filters
     *
     * @return string Generated filter PHP source
     */
    private function getFilters(array $filters, int $level): string
    {
        if (empty($filters)) {
            return '';
        }

        $name = array_shift($filters);
        $method = $this->getFindMethod($name);
        $filter = $method !== 'last' ? var_export($name, true) : '';
        $callable = $this->getCallable('$filter');
        $msg = var_export($name, true);

        return sprintf(
            $this->prepare(self::FILTER, $level),
            $method,
            $filter,
            $callable,
            $msg,
            $this->getFilters($filters, $level),
        );
    }

    /**
     * Generate Mustache Template output Buffer call PHP source.
     *
     * @return string Generated output Buffer call PHP source
     */
    private function text(string $text, int $level): string
    {
        $indentNextLine = (substr($text, -1) === "\n");
        $code = sprintf($this->prepare(self::TEXT, $level), $this->flushIndent(), var_export($text, true));
        $this->indentNextLine = $indentNextLine;

        return $code;
    }

    /**
     * Prepare PHP source code snippet for output.
     *
     * @param int    $bonus          Additional indent level (default: 0)
     * @param bool   $prependNewline Prepend a newline to the snippet? (default: true)
     * @param bool   $appendNewline  Append a newline to the snippet? (default: false)
     *
     * @return string PHP source code snippet
     */
    private function prepare(
        string $text,
        int $bonus = 0,
        bool $prependNewline = true,
        bool $appendNewline = false,
    ): string {
        $text = ($prependNewline ? "\n" : '') . trim($text);
        if ($prependNewline) {
            $bonus++;
        }

        if ($appendNewline) {
            $text .= "\n";
        }

        return preg_replace("/\n( {8})?/", "\n" . str_repeat(' ', $bonus * 4), $text);
    }

    /**
     * Get the current escaper.
     *
     * @param string $value (default: '$value')
     *
     * @return string Either a custom callback, or an inline call to `htmlspecialchars`
     */
    private function getEscape(string $value = '$value'): string
    {
        if ($this->customEscape) {
            return sprintf(self::CUSTOM_ESCAPE, $value);
        }

        return sprintf(
            self::DEFAULT_ESCAPE,
            $value,
            var_export($this->entityFlags, true),
            var_export($this->charset, true),
        );
    }

    /**
     * Select the appropriate Context `find` method for a given $id.
     *
     * The return value will be one of `find`, `findDot`, `findAnchoredDot` or `last`.
     *
     * @see Context::last
     * @see Context::find
     * @see Context::findDot
     *
     * @param string $id Variable name
     *
     * @return string `find` method name
     */
    private function getFindMethod(string $id): string
    {
        if ($id === '.') {
            return 'last';
        }

        if (isset($this->pragmas[Engine::PRAGMA_ANCHORED_DOT]) && $this->pragmas[Engine::PRAGMA_ANCHORED_DOT]) {
            if (substr($id, 0, 1) === '.') {
                return 'findAnchoredDot';
            }
        }

        if (strpos($id, '.') === false) {
            return 'find';
        }

        return 'findDot';
    }

    /**
     * Helper function to compile strict vs lax "is callable" logic.
     *
     * @param string $variable (default: '$value')
     *
     * @return string "is callable" logic
     */
    private function getCallable(string $variable = '$value'): string
    {
        $tpl = $this->strictCallables ? self::STRICT_IS_CALLABLE : self::IS_CALLABLE;

        return sprintf($tpl, $variable, $variable);
    }

    /**
     * Get the current $indent prefix to write to the buffer.
     *
     * @return string "$indent . " or ""
     */
    private function flushIndent(): string
    {
        if (! $this->indentNextLine) {
            return '';
        }

        $this->indentNextLine = false;

        return self::LINE_INDENT;
    }
}
