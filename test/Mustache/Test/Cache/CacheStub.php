<?php

declare(strict_types=1);

namespace Mustache\Test\Cache;

use Mustache\Cache\AbstractCache;

final class CacheStub extends AbstractCache
{
    public function load($key)
    {
        // nada
    }

    public function cache($key, $value)
    {
        // nada
    }
}
