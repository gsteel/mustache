<?php

declare(strict_types=1);

namespace Mustache;

use Mustache\Exception\UnknownHelperException;

interface HelperManager
{
    /**
     * Check whether a given helper is present in the collection.
     */
    public function has(string $name): bool;

    /**
     * Get a helper by name.
     *
     * @return mixed Helper
     *
     * @throws UnknownHelperException If helper does not exist.
     */
    public function get(string $name): mixed;

    /**
     * Whether this helper manager has any registered helpers
     */
    public function isEmpty(): bool;

    /**
     * Magic isset().
     *
     * @see HelperManager::has
     */
    public function __isset(string $name): bool;

    /**
     * Magic accessor.
     *
     * @see HelperManager::get
     */
    public function __get(string $name): mixed;
}
