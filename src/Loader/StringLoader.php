<?php

declare(strict_types=1);

namespace Mustache\Loader;

use Mustache\Loader;
use Mustache\Source;

/**
 * Mustache Template string Loader implementation.
 *
 * A StringLoader instance is essentially a noop. It simply passes the 'name' argument straight through:
 *
 *     $loader = new Mustache\Loader\StringLoader;
 *     $tpl = $loader->load('{{ foo }}'); // '{{ foo }}'
 *
 * This is the default Template Loader instance used by Mustache:
 *
 *     $m = new Mustache;
 *     $tpl = $m->loadTemplate('{{ foo }}');
 *     echo $tpl->render(array('foo' => 'bar')); // "bar"
 */
final class StringLoader implements Loader
{
    public function load(string $name): string|Source
    {
        return $name;
    }
}
