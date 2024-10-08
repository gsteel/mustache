<?php

declare(strict_types=1);

namespace Mustache\Loader;

use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader;
use Mustache\Source;

/**
 * A Mustache Template cascading loader implementation, which delegates to other
 * Loader instances.
 */
final class CascadingLoader implements Loader
{
    /** @var list<Loader> */
    private array $loaders;

    /**
     * Construct a Mustache\Loader\CascadingLoader with an array of loaders.
     *
     *     $loader = new Mustache_Loader_CascadingLoader([
     *         new Mustache_Loader_InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__),
     *         new Mustache\Loader(__DIR__.'/templates')
     *     ]);
     *
     * @param list<Loader> $loaders
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
     */
    public function addLoader(Loader $loader): void
    {
        $this->loaders[] = $loader;
    }

    public function load(string $name): string|Source
    {
        foreach ($this->loaders as $loader) {
            try {
                return $loader->load($name);
            } catch (UnknownTemplateException) {
                // do nothing, check the next loader.
            }
        }

        throw new UnknownTemplateException($name);
    }
}
