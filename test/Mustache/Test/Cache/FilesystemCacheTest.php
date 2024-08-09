<?php

declare(strict_types=1);

namespace Mustache\Test\Cache;

use Mustache\Cache\FilesystemCache;
use Mustache\Test\FunctionalTestCase;

/** @group functional */
class FilesystemCacheTest extends FunctionalTestCase
{
    public function testCacheGetNone(): void
    {
        $key = 'some key';
        $cache = new FilesystemCache(self::$tempDir);
        $loaded = $cache->load($key);

        $this->assertFalse($loaded);
    }

    public function testCachePut(): void
    {
        $key = 'some key';
        $value = '<?php /* some value */';
        $cache = new FilesystemCache(self::$tempDir);
        $cache->cache($key, $value);
        $loaded = $cache->load($key);

        $this->assertTrue($loaded);
    }
}
