<?php

namespace Mustache;

use Psr;

/**
 * Mustache Cache interface.
 *
 * Interface for caching and loading \Mustache\Template classes
 * generated by the \Mustache\Compiler.
 */
interface Cache
{
    /**
     * Load a compiled \Mustache\Template class from cache.
     *
     * @param string $key
     *
     * @return bool indicates successfully class load
     */
    public function load($key);

    /**
     * Mustache\Cache and load a compiled \Mustache\Template class.
     *
     * @param string $key
     * @param string $value
     */
    public function cache($key, $value);

    /**
     * Set a logger instance.
     *
     * @param Logger|Psr\Log\LoggerInterface $logger
     */
    public function setLogger($logger = null);
}
