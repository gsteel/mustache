<?php

namespace Mustache\Test\Cache;

use Mustache\Cache\FilesystemCache;
use Mustache\Test\FunctionalTestCase;

/**
 * @group functional
 */
class FilesystemCacheTest extends FunctionalTestCase
{
    public function testCacheGetNone()
    {
        $key = 'some key';
        $cache = new FilesystemCache(self::$tempDir);
        $loaded = $cache->load($key);

        $this->assertFalse($loaded);
    }

    public function testCachePut()
    {
        $key = 'some key';
        $value = '<?php /* some value */';
        $cache = new FilesystemCache(self::$tempDir);
        $cache->cache($key, $value);
        $loaded = $cache->load($key);

        $this->assertTrue($loaded);
    }
}
