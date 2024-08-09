<?php

declare(strict_types=1);

namespace Mustache\Test;

use Mustache\Engine;
use Mustache\Template;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function file_get_contents;
use function json_decode;

abstract class SpecTestCase extends TestCase
{
    protected static Engine $mustache;

    protected function setUp(): void
    {
        if (file_exists(__DIR__ . '/../../../vendor/spec/specs/')) {
            return;
        }

        $this->markTestSkipped('Mustache spec submodule not initialized: run "git submodule update --init"');
    }

    public static function setUpBeforeClass(): void
    {
        self::$mustache = new Engine();
    }

    /** @param array<string, string> $partials */
    protected static function loadTemplate(string $source, array $partials): Template
    {
        self::$mustache->setPartials($partials);

        return self::$mustache->loadTemplate($source);
    }

    /**
     * Data provider for the mustache spec test.
     *
     * Loads JSON files from the spec and converts them to PHPisms.
     *
     * @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}>
     */
    protected static function loadSpec(string $name): array
    {
        $filename = __DIR__ . '/../../../vendor/spec/specs/' . $name . '.json';
        if (! file_exists($filename)) {
            return [];
        }

        $data = [];
        $file = file_get_contents($filename);
        $spec = json_decode($file, true);

        foreach ($spec['tests'] as $test) {
            $data[] = [
                $test['name'] . ': ' . $test['desc'],
                $test['template'],
                $test['partials'] ??
            [],
                $test['data'],
                $test['expected'],
            ];
        }

        return $data;
    }
}
