<?php

declare(strict_types=1);

namespace Mustache\Test;

use PHPUnit\Framework\TestCase;

use function closedir;
use function file_exists;
use function is_dir;
use function opendir;
use function readdir;
use function rmdir;
use function rtrim;
use function sys_get_temp_dir;
use function unlink;

abstract class FunctionalTestCase extends TestCase
{
    protected static string $tempDir;

    public static function setUpBeforeClass(): void
    {
        self::$tempDir = sys_get_temp_dir() . '/mustache_test';
        if (! file_exists(self::$tempDir)) {
            return;
        }

        self::rmdir(self::$tempDir);
    }

    protected static function rmdir(string $path): void
    {
        $path = rtrim($path, '/') . '/';
        $handle = opendir($path);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullpath = $path . $file;
            if (is_dir($fullpath)) {
                self::rmdir($fullpath);
            } else {
                unlink($fullpath);
            }
        }

        closedir($handle);
        rmdir($path);
    }
}
