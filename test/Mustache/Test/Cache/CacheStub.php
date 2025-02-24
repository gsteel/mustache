<?php

declare(strict_types=1);

namespace Mustache\Test\Cache;

use Mustache\Cache\AbstractCache;
use Override;

final class CacheStub extends AbstractCache
{
    #[Override]
    public function load(string $key): bool
    {
        return false;
    }

    #[Override]
    public function cache(string $key, string $value): void
    {
        // nada
    }
}
