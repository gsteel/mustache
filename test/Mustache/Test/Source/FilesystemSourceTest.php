<?php

declare(strict_types=1);

namespace Mustache\Test\Source;

use Mustache\Exception\RuntimeException;
use Mustache\Source\FilesystemSource;
use PHPUnit\Framework\TestCase;

use function dirname;
use function restore_error_handler;
use function set_error_handler;

use const E_WARNING;

class FilesystemSourceTest extends TestCase
{
    public function testMissingTemplateThrowsException(): void
    {
        set_error_handler(static function (int $errNo, string $errorMessage): bool {
            self::assertStringContainsString('stat failed', $errorMessage);

            return true;
        }, E_WARNING);

        $source = new FilesystemSource(dirname(__FILE__) . '/not_a_file.mustache', ['mtime']);
        try {
            $source->getKey();
            self::fail('An exception should have been thrown');
        } catch (RuntimeException) {
        } finally {
            restore_error_handler();
        }
    }
}
