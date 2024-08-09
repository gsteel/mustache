<?php

declare(strict_types=1);

namespace Mustache\Test\Loader;

use Mustache\Loader\StringLoader;
use PHPUnit\Framework\TestCase;

class StringLoaderTest extends TestCase
{
    public function testLoadTemplates(): void
    {
        $loader = new StringLoader();

        $this->assertEquals('foo', $loader->load('foo'));
        $this->assertEquals('{{ bar }}', $loader->load('{{ bar }}'));
        $this->assertEquals("\n{{! comment }}\n", $loader->load("\n{{! comment }}\n"));
    }
}
