<?php

declare(strict_types=1);

namespace Mustache;

use Traversable;

use function call_user_func;
use function gettype;
use function is_callable;
use function is_object;
use function is_string;

/**
 * Abstract Mustache Template class.
 *
 * @abstract
 */
abstract class Template
{
    protected bool $strictCallables = false;

    /**
     * Mustache Template constructor.
     */
    public function __construct(protected Engine $mustache)
    {
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
    public function __invoke(mixed $context = []): string
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
     * between between a list of things (numeric, normalized array) and a set of variables to be used as section context
     * (associative array). In other words, this will be iterated over:
     *
     *     $items = array(
     *         array('name' => 'foo'),
     *         array('name' => 'bar'),
     *         array('name' => 'baz'),
     *     );
     *
     * ... but this will be used as a section context block:
     *
     *     $items = array(
     *         1        => array('name' => 'foo'),
     *         'banana' => array('name' => 'bar'),
     *         42       => array('name' => 'baz'),
     *     );
     *
     * @return bool True if the value is 'iterable'
     */
    protected function isIterable(mixed $value): bool
    {
        switch (gettype($value)) {
            case 'object':
                return $value instanceof Traversable;

            case 'array':
                $i = 0;
                foreach ($value as $k => $v) {
                    if ($k !== $i++) {
                        return false;
                    }
                }

                return true;

            default:
                return false;
        }
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
        $stack = new Context(null, $this->mustache->useBuggyPropertyShadowing());

        $helpers = $this->mustache->getHelpers();
        if (! $helpers->isEmpty()) {
            $stack->push($helpers);
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
        if (($this->strictCallables ? is_object($value) : ! is_string($value)) && is_callable($value)) {
            return $this->mustache
                ->loadLambda((string) call_user_func($value))
                ->renderInternal($context);
        }

        return $value;
    }
}
