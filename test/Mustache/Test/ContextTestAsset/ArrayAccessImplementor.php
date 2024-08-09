<?php

declare(strict_types=1);

namespace Mustache\Test\ContextTestAsset;

use ArrayAccess;

/** @implements ArrayAccess<array-key, mixed> */
final class ArrayAccessImplementor implements ArrayAccess
{
    /** @var array<array-key, mixed> */
    private array $container = [];

    /** @param array<array-key, mixed> $array */
    public function __construct(array $array)
    {
        foreach ($array as $key => $value) {
            $this->container[$key] = $value;
        }
    }

    /** @param array-key|null $offset */
    public function offsetSet($offset, mixed $value): void
    {
        if ($offset === null) {
            $this->container[] = $value;
        } else {
            $this->container[$offset] = $value;
        }
    }

    /** @param array-key $offset */
    public function offsetExists($offset): bool
    {
        return isset($this->container[$offset]);
    }

    /** @param array-key $offset */
    public function offsetUnset($offset): void
    {
        unset($this->container[$offset]);
    }

    /** @param array-key $offset */
    public function offsetGet($offset): mixed
    {
        return $this->container[$offset] ?? null;
    }
}
