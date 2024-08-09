<?php

namespace Mustache\Test;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_exists;

abstract class SpecTestCase extends TestCase
{
    protected static $mustache;

    protected function setUp(): void
    {
        if (! file_exists(__DIR__ . '/../../../vendor/spec/specs/')) {
            $this->markTestSkipped('Mustache spec submodule not initialized: run "git submodule update --init"');
        }
    }

    public static function setUpBeforeClass(): void
    {
        self::$mustache = new Engine();
    }

    protected static function loadTemplate($source, $partials)
    {
        self::$mustache->setPartials($partials);

        return self::$mustache->loadTemplate($source);
    }

    /**
     * Data provider for the mustache spec test.
     *
     * Loads JSON files from the spec and converts them to PHPisms.
     *
     * @param string $name
     *
     * @return array
     */
    protected function loadSpec($name)
    {
        $filename = dirname(__FILE__) . '/../../../vendor/spec/specs/' . $name . '.json';
        if (!file_exists($filename)) {
            return array();
        }

        $data = array();
        $file = file_get_contents($filename);
        $spec = json_decode($file, true);

        foreach ($spec['tests'] as $test) {
            $data[] = array(
                $test['name'] . ': ' . $test['desc'],
                $test['template'],
                isset($test['partials']) ? $test['partials'] : array(),
                $test['data'],
                $test['expected'],
            );
        }

        return $data;
    }
}
