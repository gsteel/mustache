<?php

declare(strict_types=1);

namespace Mustache;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownHelperException;
use Traversable;

use function array_key_exists;
use function is_array;

/**
 * A collection of helpers for a Mustache instance.
 */
class HelperCollection
{
    /** @var array<string, mixed> */
    private array $helpers = [];

    /**
     * Helper Collection constructor.
     *
     * Optionally accepts an array (or Traversable) of `$name => $helper` pairs.
     *
     * @param array<string, mixed>|Traversable<string, mixed> $helpers (default: null)
     *
     * @throws InvalidArgumentException if the $helpers argument isn't an array or Traversable.
     */
    public function __construct($helpers = null)
    {
        if ($helpers === null) {
            return;
        }

        if (! is_array($helpers) && ! $helpers instanceof Traversable) {
            throw new InvalidArgumentException('Mustache\HelperCollection constructor expects an array of helpers');
        }

        foreach ($helpers as $name => $helper) {
            $this->add($name, $helper);
        }
    }

    /**
     * Magic mutator.
     *
     * @see HelperCollection::add
     *
     * @param mixed $helper
     */
    public function __set(string $name, $helper): void
    {
        $this->add($name, $helper);
    }

    /**
     * Add a helper to this collection.
     *
     * @param mixed $helper
     */
    public function add(string $name, $helper): void
    {
        $this->helpers[$name] = $helper;
    }

    /**
     * Magic accessor.
     *
     * @see HelperCollection::get
     *
     * @return mixed Helper
     */
    public function __get(string $name)
    {
        return $this->get($name);
    }

    /**
     * Get a helper by name.
     *
     * @return mixed Helper
     *
     * @throws UnknownHelperException If helper does not exist.
     */
    public function get(string $name)
    {
        if (! $this->has($name)) {
            throw new UnknownHelperException($name);
        }

        return $this->helpers[$name];
    }

    /**
     * Magic isset().
     *
     * @see HelperCollection::has
     *
     * @return bool True if helper is present
     */
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    /**
     * Check whether a given helper is present in the collection.
     *
     * @return bool True if helper is present
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->helpers);
    }

    /**
     * Magic unset().
     *
     * @see HelperCollection::remove
     */
    public function __unset(string $name): void
    {
        $this->remove($name);
    }

    /**
     * Check whether a given helper is present in the collection.
     *
     * @throws UnknownHelperException if the requested helper is not present.
     */
    public function remove(string $name): void
    {
        if (! $this->has($name)) {
            throw new UnknownHelperException($name);
        }

        unset($this->helpers[$name]);
    }

    /**
     * Clear the helper collection.
     *
     * Removes all helpers from this collection
     */
    public function clear(): void
    {
        $this->helpers = [];
    }

    /**
     * Check whether the helper collection is empty.
     *
     * @return bool True if the collection is empty
     */
    public function isEmpty(): bool
    {
        return empty($this->helpers);
    }
}
