<?php

namespace Mustache\Loader;

/**
 * Mustache Template mutable Loader interface.
 */
interface MutableLoader
{
    /**
     * Set an associative array of Template sources for this loader.
     *
     * @param array $templates
     */
    public function setTemplates(array $templates);

    /**
     * Set a Template source by name.
     *
     * @param string $name
     * @param string $template Mustache Template source
     */
    public function setTemplate($name, $template);
}
