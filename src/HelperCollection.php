<?php

namespace Mustache;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownHelperException;

/**
 * A collection of helpers for a Mustache instance.
 */
class HelperCollection
{
    private $helpers = [];

    /**
     * Helper Collection constructor.
     *
     * Optionally accepts an array (or Traversable) of `$name => $helper` pairs.
     *
     * @throws InvalidArgumentException if the $helpers argument isn't an array or Traversable
     *
     * @param array|Traversable $helpers (default: null)
     */
    public function __construct($helpers = null)
    {
        if ($helpers === null) {
            return;
        }

        if (!is_array($helpers) && !$helpers instanceof Traversable) {
            throw new InvalidArgumentException('Mustache\HelperCollection constructor expects an array of helpers');
        }

        foreach ($helpers as $name => $helper) {
            $this->add($name, $helper);
        }
    }

    /**
     * Magic mutator.
     *
     * @param string $name
     * @param mixed $helper
     * @see HelperCollection::add
     *
     */
    public function __set($name, $helper)
    {
        $this->add($name, $helper);
    }

    /**
     * Add a helper to this collection.
     *
     * @param string $name
     * @param mixed $helper
     */
    public function add($name, $helper)
    {
        $this->helpers[$name] = $helper;
    }

    /**
     * Magic accessor.
     *
     * @param string $name
     *
     * @return mixed Helper
     * @see HelperCollection::get
     *
     */
    public function __get($name)
    {
        return $this->get($name);
    }

    /**
     * Get a helper by name.
     *
     * @param string $name
     *
     * @return mixed Helper
     * @throws UnknownHelperException If helper does not exist
     *
     */
    public function get($name)
    {
        if (!$this->has($name)) {
            throw new UnknownHelperException($name);
        }

        return $this->helpers[$name];
    }

    /**
     * Magic isset().
     *
     * @param string $name
     *
     * @return bool True if helper is present
     * @see HelperCollection::has
     *
     */
    public function __isset($name)
    {
        return $this->has($name);
    }

    /**
     * Check whether a given helper is present in the collection.
     *
     * @param string $name
     *
     * @return bool True if helper is present
     */
    public function has($name)
    {
        return array_key_exists($name, $this->helpers);
    }

    /**
     * Magic unset().
     *
     * @param string $name
     * @see HelperCollection::remove
     *
     */
    public function __unset($name)
    {
        $this->remove($name);
    }

    /**
     * Check whether a given helper is present in the collection.
     *
     * @param string $name
     * @throws UnknownHelperException if the requested helper is not present
     *
     */
    public function remove($name)
    {
        if (!$this->has($name)) {
            throw new UnknownHelperException($name);
        }

        unset($this->helpers[$name]);
    }

    /**
     * Clear the helper collection.
     *
     * Removes all helpers from this collection
     */
    public function clear()
    {
        $this->helpers = [];
    }

    /**
     * Check whether the helper collection is empty.
     *
     * @return bool True if the collection is empty
     */
    public function isEmpty()
    {
        return empty($this->helpers);
    }
}
