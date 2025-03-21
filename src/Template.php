<?php

declare(strict_types=1);

namespace Mustache;

use Traversable;

use function array_is_list;
use function assert;
use function call_user_func;
use function gettype;
use function is_callable;
use function is_object;
use function is_string;

abstract class Template
{
    /**
     * Mustache Template constructor.
     */
    final public function __construct(
        protected Engine $mustache,
        protected HelperCollection $helpers,
        protected bool $strictCallables,
    ) {
    }

    /**
     * Mustache Template instances can be treated as a function and rendered by simply calling them.
     *
     *     $m = new Mustache\Engine;
     *     $tpl = $m->loadTemplate('Hello, {{ name }}!');
     *     echo $tpl(array('name' => 'World')); // "Hello, World!"
     *
     * @see Template::render
     *
     * @param mixed $context Array or object rendering context (default: array())
     *
     * @return string Rendered template
     */
    final public function __invoke(mixed $context = []): string
    {
        return $this->render($context);
    }

    /**
     * Render this template given the rendering context.
     *
     * @param mixed $context Array or object rendering context (default: array())
     *
     * @return string Rendered template
     */
    public function render(mixed $context = []): string
    {
        return $this->renderInternal(
            $this->prepareContextStack($context),
        );
    }

    /**
     * Internal rendering method implemented by Mustache Template concrete subclasses.
     *
     * This is where the magic happens :)
     *
     * NOTE: This method is not part of the Mustache.php public API.
     *
     * @param string $indent (default: '')
     *
     * @return string Rendered template
     */
    abstract public function renderInternal(Context $context, string $indent = ''): string;

    /**
     * Tests whether a value should be iterated over (e.g. in a section context).
     *
     * In most languages there are two distinct array types: list and hash (or whatever you want to call them). Lists
     * should be iterated, hashes should be treated as objects. Mustache follows this paradigm for Ruby, Javascript,
     * Java, Python, etc.
     *
     * PHP, however, treats lists and hashes as one primitive type: array. So Mustache.php needs a way to distinguish
     * between a list of things (numeric, normalized array) and a set of variables to be used as section context
     * (associative array). In other words, this will be iterated over:
     *
     *     $items = [
     *         ['name' => 'foo'],
     *         ['name' => 'bar'],
     *         ['name' => 'baz'],
     *     ];
     *
     * ... but this will be used as a section context block:
     *
     *     $items = [
     *         1        => ['name' => 'foo'],
     *         'banana' => ['name' => 'bar'],
     *         42       => ['name' => 'baz'],
     *     ];
     */
    protected function isIterable(mixed $value): bool
    {
        return match (gettype($value)) {
            'object' => $value instanceof Traversable,
            'array' => array_is_list($value),
            default => false,
        };
    }

    /**
     * Helper method to prepare the Context stack.
     *
     * Adds the Mustache HelperCollection to the stack's top context frame if helpers are present.
     *
     * @param mixed $context Optional first context frame (default: null)
     */
    protected function prepareContextStack(mixed $context = null): Context
    {
        $stack = new Context(null);

        if (! $this->helpers->isEmpty()) {
            $stack->push($this->helpers);
        }

        if (! empty($context)) {
            $stack->push($context);
        }

        return $stack;
    }

    /**
     * Resolve a context value.
     *
     * Invoke the value if it is callable, otherwise return the value.
     */
    protected function resolveValue(mixed $value, Context $context): mixed
    {
        $isCallable = $this->strictCallables
            ? is_object($value) && is_callable($value)
            : ! is_string($value) && is_callable($value);

        if ($isCallable) {
            assert(is_callable($value));

            return $this->mustache
                ->loadLambda((string) call_user_func($value))
                ->renderInternal($context);
        }

        return $value;
    }
}
