<?php

namespace Mustache\Test;

use Mustache\Autoloader;
use PHPUnit\Framework\TestCase;

class AutoloaderTest extends TestCase
{
    public function testRegister()
    {
        $loader = Autoloader::register();
        $this->assertTrue(spl_autoload_unregister([$loader, 'autoload']));
    }

    public function testAutoloader()
    {
        $loader = new Autoloader(dirname(__FILE__) . '/../../fixtures/autoloader');

        $this->assertNull($loader->autoload('NonMustacheClass'));
        $this->assertFalse(class_exists('NonMustacheClass'));

        $loader->autoload('Mustache_Foo');
        $this->assertTrue(class_exists('Mustache_Foo'));

        $loader->autoload('\Mustache_Bar');
        $this->assertTrue(class_exists('Mustache_Bar'));
    }

    /**
     * Test that the autoloader won't register multiple times.
     */
    public function testRegisterMultiple()
    {
        $numLoaders = count(spl_autoload_functions());

        Autoloader::register();
        Autoloader::register();

        $expectedNumLoaders = $numLoaders + 1;

        $this->assertCount($expectedNumLoaders, spl_autoload_functions());
    }
}
