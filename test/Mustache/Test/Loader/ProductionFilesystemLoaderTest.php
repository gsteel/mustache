<?php

declare(strict_types=1);

namespace Mustache\Test\Loader;

use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\ProductionFilesystemLoader;
use Mustache\Source;
use PHPUnit\Framework\TestCase;

use function dirname;
use function realpath;

final class ProductionFilesystemLoaderTest extends TestCase
{
    public function testConstructor(): void
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        self::assertIsString($baseDir);
        $loader = new ProductionFilesystemLoader($baseDir, ['extension' => '.ms']);
        $this->assertInstanceOf(Source::class, $loader->load('alpha'));
        $this->assertEquals('alpha contents', (string) $loader->load('alpha'));
        $this->assertInstanceOf(Source::class, $loader->load('beta.ms'));
        $this->assertEquals('beta contents', (string) $loader->load('beta.ms'));
    }

    public function testTrailingSlashes(): void
    {
        $baseDir = dirname(__FILE__) . '/../../../fixtures/templates/';
        $loader = new ProductionFilesystemLoader($baseDir);
        $this->assertEquals('one contents', (string) $loader->load('one'));
    }

    public function testConstructorWithProtocol(): void
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        self::assertIsString($baseDir);

        $loader = new ProductionFilesystemLoader('file://' . $baseDir, ['extension' => '.ms']);
        $this->assertEquals('alpha contents', (string) $loader->load('alpha'));
        $this->assertEquals('beta contents', (string) $loader->load('beta.ms'));
    }

    public function testLoadTemplates(): void
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        self::assertIsString($baseDir);
        $loader = new ProductionFilesystemLoader($baseDir);
        $this->assertEquals('one contents', (string) $loader->load('one'));
        $this->assertEquals('two contents', (string) $loader->load('two.mustache'));
    }

    public function testEmptyExtensionString(): void
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        self::assertIsString($baseDir);

        $loader = new ProductionFilesystemLoader($baseDir, ['extension' => '']);
        $this->assertEquals('one contents', (string) $loader->load('one.mustache'));
        $this->assertEquals('alpha contents', (string) $loader->load('alpha.ms'));

        $loader = new ProductionFilesystemLoader($baseDir, ['extension' => null]);
        $this->assertEquals('two contents', (string) $loader->load('two.mustache'));
        $this->assertEquals('beta contents', (string) $loader->load('beta.ms'));
    }

    public function testMissingBaseDirThrowsException(): void
    {
        $this->expectException(RuntimeException::class);
        new ProductionFilesystemLoader(dirname(__FILE__) . '/not_a_directory');
    }

    public function testMissingTemplateThrowsException(): void
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        self::assertIsString($baseDir);
        $loader = new ProductionFilesystemLoader($baseDir);

        $this->expectException(UnknownTemplateException::class);
        $loader->load('fake');
    }

    public function testLoadWithDifferentStatProps(): void
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        self::assertIsString($baseDir);
        $noStatLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => null]);
        $mtimeLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => ['mtime']]);
        $sizeLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => ['size']]);
        $bothLoader = new ProductionFilesystemLoader($baseDir, ['stat_props' => ['mtime', 'size']]);

        $noStatSource = $noStatLoader->load('one.mustache');
        $mtimeSource = $mtimeLoader->load('one.mustache');
        $sizeSource = $sizeLoader->load('one.mustache');
        $bothSource = $bothLoader->load('one.mustache');

        self::assertInstanceOf(Source::class, $noStatSource);
        self::assertInstanceOf(Source::class, $mtimeSource);
        self::assertInstanceOf(Source::class, $sizeSource);
        self::assertInstanceOf(Source::class, $bothSource);

        $noStatKey = $noStatSource->getKey();
        $mtimeKey = $mtimeSource->getKey();
        $sizeKey = $sizeSource->getKey();
        $bothKey = $bothSource->getKey();

        $this->assertNotEquals($noStatKey, $mtimeKey);
        $this->assertNotEquals($noStatKey, $sizeKey);
        $this->assertNotEquals($noStatKey, $bothKey);
        $this->assertNotEquals($mtimeKey, $sizeKey);
        $this->assertNotEquals($mtimeKey, $bothKey);
        $this->assertNotEquals($sizeKey, $bothKey);
    }
}
