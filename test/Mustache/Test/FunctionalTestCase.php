<?php

namespace Mustache\Test;

use PHPUnit\Framework\TestCase;

abstract class FunctionalTestCase extends TestCase
{
    protected static $tempDir;

    public static function setUpBeforeClass(): void
    {
        self::$tempDir = sys_get_temp_dir() . '/mustache_test';
        if (file_exists(self::$tempDir)) {
            self::rmdir(self::$tempDir);
        }
    }

    /**
     * @param string $path
     */
    protected static function rmdir($path)
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
