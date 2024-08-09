<?php

namespace Mustache;

/**
 * Mustache Lambda Helper.
 *
 * Passed as the second argument to section lambdas (higher order sections),
 * giving them access to a `render` method for rendering a string with the
 * current context.
 */
class LambdaHelper
{
    private $mustache;
    private $context;
    private $delims;

    /**
     * Mustache Lambda Helper constructor.
     *
     * @param Engine $mustache Mustache engine instance
     * @param Context $context Rendering context
     * @param string $delims   Optional custom delimiters, in the format `{{= <% %> =}}`. (default: null)
     */
    public function __construct(Engine $mustache, Context $context, $delims = null)
    {
        $this->mustache = $mustache;
        $this->context = $context;
        $this->delims = $delims;
    }

    /**
     * Render a string as a Mustache template with the current rendering context.
     *
     * @param string $string
     *
     * @return string Rendered template
     */
    public function render($string)
    {
        return $this->mustache
            ->loadLambda((string)$string, $this->delims)
            ->renderInternal($this->context);
    }

    /**
     * Render a string as a Mustache template with the current rendering context.
     *
     * @param string $string
     *
     * @return string Rendered template
     */
    public function __invoke($string)
    {
        return $this->render($string);
    }

    /**
     * Get a Lambda Helper with custom delimiters.
     *
     * @param string $delims Custom delimiters, in the format `{{= <% %> =}}`
     *
     * @return LambdaHelper
     */
    public function withDelimiters($delims)
    {
        return new self($this->mustache, $this->context, $delims);
    }
}
