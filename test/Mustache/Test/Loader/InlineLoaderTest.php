<?php

declare(strict_types=1);

namespace Mustache\Test\Loader;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\InlineLoader;
use PHPUnit\Framework\TestCase;

/**
 * phpcs:ignoreFile
 */
class InlineLoaderTest extends TestCase
{
    public function testLoadTemplates(): void
    {
        $loader = new InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $this->assertEquals('{{ foo }}', $loader->load('foo'));
        $this->assertEquals('{{#bar}}BAR{{/bar}}', $loader->load('bar'));
    }

    public function testMissingTemplatesThrowExceptions(): void
    {
        $loader = new InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
        $this->expectException(UnknownTemplateException::class);
        $loader->load('not_a_real_template');
    }

    public function testInvalidOffsetThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new InlineLoader(__FILE__, -10);
    }

    public function testInvalidFileThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new InlineLoader('notarealfile', __COMPILER_HALT_OFFSET__);
    }
}

__halt_compiler();

@@ foo
{{ foo }}

@@ bar
{{#bar}}BAR{{/bar}}
