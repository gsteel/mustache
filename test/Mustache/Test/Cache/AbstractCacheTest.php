<?php

declare(strict_types=1);

namespace Mustache\Test\Cache;

use Monolog\Logger;
use PHPUnit\Framework\TestCase;

class AbstractCacheTest extends TestCase
{
    public function testGetSetLogger(): void
    {
        $cache  = new CacheStub();
        $logger = new Logger('Foo');
        $cache->setLogger($logger);
        $this->assertSame($logger, $cache->getLogger());
    }
}
