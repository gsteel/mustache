<?php

namespace Mustache\Loader;

use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader;

/**
 * A Mustache Template cascading loader implementation, which delegates to other
 * Loader instances.
 */
class CascadingLoader implements Loader
{
    private $loaders;

    /**
     * Construct a Mustache\Loader\CascadingLoader with an array of loaders.
     *
     *     $loader = new Mustache_Loader_CascadingLoader([
     *         new Mustache_Loader_InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__),
     *         new Mustache\Loader(__DIR__.'/templates')
     *     ]);
     *
     * @param Loader[] $loaders
     */
    public function __construct(array $loaders = [])
    {
        $this->loaders = [];
        foreach ($loaders as $loader) {
            $this->addLoader($loader);
        }
    }

    /**
     * Add a Mustache\Loader instance.
     *
     * @param Loader $loader
     */
    public function addLoader(Loader $loader)
    {
        $this->loaders[] = $loader;
    }

    /**
     * Load a Template by name.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     * @throws UnknownTemplateException If a template file is not found
     *
     */
    public function load($name)
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($name);
            } catch (UnknownTemplateException $e) {
                // do nothing, check the next loader.
            }
        }

        throw new UnknownTemplateException($name);
    }
}
