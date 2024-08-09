<?php

declare(strict_types=1);

namespace Mustache\Loader;

use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader;
use Mustache\Source;

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
    /** @param array<string, string> $templates Associative array of Template source (default: []) */
    public function __construct(private array $templates = [])
    {
    }

    public function load(string $name): string|Source
    {
        if (! isset($this->templates[$name])) {
            throw new UnknownTemplateException($name);
        }

        return $this->templates[$name];
    }

    /**
     * Set an associative array of Template sources for this loader.
     *
     * @param array<string, string> $templates
     */
    public function setTemplates(array $templates): void
    {
        $this->templates = $templates;
    }

    /**
     * Set a Template source by name.
     *
     * @param string $template Mustache Template source
     */
    public function setTemplate(string $name, string $template): void
    {
        $this->templates[$name] = $template;
    }
}
