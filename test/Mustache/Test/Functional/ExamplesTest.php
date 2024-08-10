<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

use function closedir;
use function file_get_contents;
use function is_dir;
use function is_file;
use function opendir;
use function pathinfo;
use function readdir;
use function realpath;

/**
 * @group examples
 * @group functional
 */
class ExamplesTest extends TestCase
{
    /**
     * Test everything in the `examples` directory.
     *
     * @param array<string, string> $partials
     *
     * @dataProvider getExamples
     */
    public function testExamples(object $context, string $source, array $partials, string $expected): void
    {
        $mustache = new Engine([
            'partials' => $partials,
            'strict_callables' => false,
        ]);
        $this->assertEquals($expected, $mustache->loadTemplate($source)->render($context));
    }

    /**
     * Data provider for testExamples method.
     *
     * Loads examples from the test fixtures directory.
     *
     * This examples directory should contain any number of subdirectories, each of which contains
     * three files: one Mustache class (.php), one Mustache template (.mustache), and one output file
     * (.txt). Optionally, the directory may contain a folder full of partials.
     *
     * @return list<array{0: object, 1: string, 2: array<string, string>, 3: string}>
     */
    public static function getExamples(): array
    {
        $path     = realpath(__DIR__ . '/../../../fixtures/examples');
        $examples = [];

        $handle   = opendir($path);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullpath = $path . '/' . $file;
            if (! is_dir($fullpath)) {
                continue;
            }

            $examples[$file] = self::loadExample($fullpath);
        }

        closedir($handle);

        return $examples;
    }

    /**
     * Helper method to load an example given the full path.
     *
     * @return array{0: object, 1: string, 2: array<string, string>, 3: string}
     */
    private static function loadExample(string $path): array
    {
        $context  = null;
        $source   = null;
        $partials = [];
        $expected = null;

        $handle = opendir($path);
        while (($file = readdir($handle)) !== false) {
            $fullpath = $path . '/' . $file;
            $info = pathinfo($fullpath);

            if (is_dir($fullpath) && $info['basename'] === 'partials') {
                // load partials
                $partials = self::loadPartials($fullpath);
            } elseif (is_file($fullpath)) {
                // load other files
                switch ($info['extension']) {
                    case 'php':
                        require_once $fullpath;
                        $className = $info['filename'];
                        $context   = new $className();
                        break;

                    case 'mustache':
                        $source   = file_get_contents($fullpath);
                        break;

                    case 'txt':
                        $expected = file_get_contents($fullpath);
                        break;
                }
            }
        }

        closedir($handle);

        return [$context, $source, $partials, $expected];
    }

    /**
     * Helper method to load partials given an example directory.
     *
     * @return array<string, string> $partials
     */
    private static function loadPartials(string $path): array
    {
        $partials = [];

        $handle = opendir($path);
        while (($file = readdir($handle)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $fullpath = $path . '/' . $file;
            $info = pathinfo($fullpath);

            if ($info['extension'] !== 'mustache') {
                continue;
            }

            $partials[$info['filename']] = file_get_contents($fullpath);
        }

        closedir($handle);

        return $partials;
    }
}
