<?php

declare(strict_types=1);

namespace Mustache\Test\ContextTestAsset;

use ArrayAccess;

/** @implements ArrayAccess<array-key, mixed> */
final class AllTheThings implements ArrayAccess
{
    public string $foo  = 'fail';
    public string $bar  = 'win';
    private string $qux = 'fail';

    public function foo(): string
    {
        return 'win';
    }

    /** @param array-key $offset */
    public function offsetExists($offset): bool
    {
        return true;
    }

    /** @param array-key $offset */
    public function offsetGet($offset): string
    {
        switch ($offset) {
            case 'foo':
            case 'bar':
                return 'fail';

            case 'baz':
            case 'qux':
                return 'win';

            default:
                return 'lolwhut';
        }
    }

    /**
     * @param array-key $offset
     * @param mixed $value
     */
    public function offsetSet($offset, $value): void
    {
        // nada
    }

    /** @param array-key $offset */
    public function offsetUnset($offset): void
    {
        // nada
    }
}
