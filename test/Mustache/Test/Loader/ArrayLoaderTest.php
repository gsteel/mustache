<?php

declare(strict_types=1);

namespace Mustache\Test\Loader;

use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\ArrayLoader;
use PHPUnit\Framework\TestCase;

class ArrayLoaderTest extends TestCase
{
    public function testConstructor(): void
    {
        $loader = new ArrayLoader([
            'foo' => 'bar',
        ]);

        $this->assertEquals('bar', $loader->load('foo'));
    }

    public function testSetAndLoadTemplates(): void
    {
        $loader = new ArrayLoader([
            'foo' => 'bar',
        ]);
        $this->assertEquals('bar', $loader->load('foo'));

        $loader->setTemplate('baz', 'qux');
        $this->assertEquals('qux', $loader->load('baz'));

        $loader->setTemplates([
            'foo' => 'FOO',
            'baz' => 'BAZ',
        ]);
        $this->assertEquals('FOO', $loader->load('foo'));
        $this->assertEquals('BAZ', $loader->load('baz'));
    }

    public function testMissingTemplatesThrowExceptions(): void
    {
        $loader = new ArrayLoader();
        $this->expectException(UnknownTemplateException::class);
        $loader->load('not_a_real_template');
    }
}
