<?php

declare(strict_types=1);

namespace Mustache\Test\Cache;

use Mustache\Cache\AbstractCache;

final class CacheStub extends AbstractCache
{
    public function load(string $key): bool
    {
        return false;
    }

    public function cache(string $key, string $value): void
    {
        // nada
    }
}
