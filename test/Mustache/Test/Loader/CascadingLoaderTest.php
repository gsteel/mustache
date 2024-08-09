<?php

namespace Mustache\Test\Loader;

use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\ArrayLoader;
use Mustache\Loader\CascadingLoader;
use PHPUnit\Framework\TestCase;

class CascadingLoaderTest extends TestCase
{
    public function testLoadTemplates()
    {
        $loader = new CascadingLoader([
            new ArrayLoader(['foo' => '{{ foo }}']),
            new ArrayLoader(['bar' => '{{#bar}}BAR{{/bar}}']),
        ]);

        $this->assertEquals('{{ foo }}', $loader->load('foo'));
        $this->assertEquals('{{#bar}}BAR{{/bar}}', $loader->load('bar'));
    }

    public function testMissingTemplatesThrowExceptions()
    {
        $loader = new CascadingLoader([
            new ArrayLoader(['foo' => '{{ foo }}']),
            new ArrayLoader(['bar' => '{{#bar}}BAR{{/bar}}']),
        ]);

        $this->expectException(UnknownTemplateException::class);
        $loader->load('not_a_real_template');
    }
}
