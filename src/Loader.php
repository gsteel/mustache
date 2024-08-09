<?php

namespace Mustache;

use UnknownTemplateException;

/**
 * Mustache Template Loader interface.
 */
interface Loader
{
    /**
     * Load a Template by name.
     *
     * @param string $name
     *
     * @return string|Source Mustache Template source
     * @throws UnknownTemplateException If a template file is not found
     *
     */
    public function load($name);
}
