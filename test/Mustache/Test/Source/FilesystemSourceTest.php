<?php

namespace Mustache\Test\Source;

use Mustache\Exception\RuntimeException;
use Mustache\Source\FilesystemSource;
use PHPUnit\Framework\TestCase;

class FilesystemSourceTest extends TestCase
{
    public function testMissingTemplateThrowsException()
    {
        $source = new FilesystemSource(dirname(__FILE__) . '/not_a_file.mustache', array('mtime'));
        $this->expectException(RuntimeException::class);
        $source->getKey();
    }
}
