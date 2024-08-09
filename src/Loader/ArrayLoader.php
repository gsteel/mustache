<?php

namespace Mustache\Loader;

use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader;

/**
 * Mustache Template array Loader implementation.
 *
 * An ArrayLoader instance loads Mustache Template source by name from an initial array:
 *
 *     $loader = new ArrayLoader(
 *         'foo' => '{{ bar }}',
 *         'baz' => 'Hey {{ qux }}!'
 *     );
 *
 *     $tpl = $loader->load('foo'); // '{{ bar }}'
 *
 * The ArrayLoader is used internally as a partials loader by Mustache_Engine instance when an array of partials
 * is set. It can also be used as a quick-and-dirty Template loader.
 */
class ArrayLoader implements Loader, MutableLoader
{
    private $templates;

    /**
     * ArrayLoader constructor.
     *
     * @param array $templates Associative array of Template source (default: [])
     */
    public function __construct(array $templates = [])
    {
        $this->templates = $templates;
    }

    /**
     * Load a Template.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     * @throws UnknownTemplateException If a template file is not found
     *
     */
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            throw new UnknownTemplateException($name);
        }

        return $this->templates[$name];
    }

    /**
     * Set an associative array of Template sources for this loader.
     *
     * @param array $templates
     */
    public function setTemplates(array $templates)
    {
        $this->templates = $templates;
    }

    /**
     * Set a Template source by name.
     *
     * @param string $name
     * @param string $template Mustache Mustache\Template source
     */
    public function setTemplate($name, $template)
    {
        $this->templates[$name] = $template;
    }
}
