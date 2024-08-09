<?php

namespace Mustache\Test\Loader;

use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\FilesystemLoader;
use Mustache\Test\Asset\TestStreamWrapper;
use PHPUnit\Framework\TestCase;
use TestStream;

use function stream_wrapper_register;
use function stream_wrapper_unregister;

class FilesystemLoaderTest extends TestCase
{
    public function testConstructor()
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        $loader = new FilesystemLoader($baseDir, array('extension' => '.ms'));
        $this->assertEquals('alpha contents', $loader->load('alpha'));
        $this->assertEquals('beta contents', $loader->load('beta.ms'));
    }

    public function testTrailingSlashes()
    {
        $baseDir = dirname(__FILE__) . '/../../../fixtures/templates/';
        $loader = new FilesystemLoader($baseDir);
        $this->assertEquals('one contents', $loader->load('one'));
    }

    public function testConstructorWithProtocol()
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');

        if (! stream_wrapper_register('test', TestStreamWrapper::class)) {
            self::fail('Could not register stream wrapper');
        }

        $loader = new FilesystemLoader('test://' . $baseDir, array('extension' => '.ms'));
        $this->assertEquals('alpha contents', $loader->load('alpha'));
        $this->assertEquals('beta contents', $loader->load('beta.ms'));

        stream_wrapper_unregister('test');
    }

    public function testLoadTemplates()
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        $loader = new FilesystemLoader($baseDir);
        $this->assertEquals('one contents', $loader->load('one'));
        $this->assertEquals('two contents', $loader->load('two.mustache'));
    }

    public function testEmptyExtensionString()
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');

        $loader = new FilesystemLoader($baseDir, array('extension' => ''));
        $this->assertEquals('one contents', $loader->load('one.mustache'));
        $this->assertEquals('alpha contents', $loader->load('alpha.ms'));

        $loader = new FilesystemLoader($baseDir, array('extension' => null));
        $this->assertEquals('two contents', $loader->load('two.mustache'));
        $this->assertEquals('beta contents', $loader->load('beta.ms'));
    }

    public function testMissingBaseDirThrowsException()
    {
        $this->expectException(RuntimeException::class);
        new FilesystemLoader(dirname(__FILE__) . '/not_a_directory');
    }

    public function testMissingTemplateThrowsException()
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../../fixtures/templates');
        $loader = new FilesystemLoader($baseDir);

        $this->expectException(UnknownTemplateException::class);
        $loader->load('fake');
    }
}
