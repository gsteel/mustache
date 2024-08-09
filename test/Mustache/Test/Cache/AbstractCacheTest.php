<?php

namespace Mustache\Test\Cache;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Logger\StreamLogger;
use PHPUnit\Framework\TestCase;
use stdClass;

class AbstractCacheTest extends TestCase
{
    public function testGetSetLogger()
    {
        $cache  = new CacheStub();
        $logger = new StreamLogger('php://stdout');
        $cache->setLogger($logger);
        $this->assertSame($logger, $cache->getLogger());
    }

    public function testSetLoggerThrowsExceptions()
    {
        $cache  = new CacheStub();
        $logger = new stdClass();
        $this->expectException(InvalidArgumentException::class);
        $cache->setLogger($logger);
    }
}
