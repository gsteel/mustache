<?php

declare(strict_types=1);

namespace Mustache\Test\Source;

use Mustache\Exception\RuntimeException;
use Mustache\Source\FilesystemSource;
use PHPUnit\Framework\TestCase;

use function dirname;

class FilesystemSourceTest extends TestCase
{
    public function testMissingTemplateThrowsException(): void
    {
        $source = new FilesystemSource(dirname(__FILE__) . '/not_a_file.mustache', ['mtime']);
        try {
            $this->expectWarning();
            $source->getKey();
            self::fail('An exception should have been thrown');
        } catch (RuntimeException $e) {
            $this->expectNotToPerformAssertions();
        }
    }
}
