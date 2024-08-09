<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Test\SpecTestCase;

/**
 * A PHPUnit test case wrapping the Mustache Spec.
 *
 * @group mustache-spec
 * @group functional
 */
class MustacheInheritanceSpecTest extends SpecTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$mustache = new Engine([
            'pragmas' => [Engine::PRAGMA_BLOCKS],
        ]);
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     *
     * @group inheritance
     * @dataProvider loadInheritanceSpec
     */
    public function testInheritanceSpec(
        string $desc,
        string $source,
        array $partials,
        array $data,
        string $expected,
    ): void {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    /** @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}> */
    public static function loadInheritanceSpec(): array
    {
        // return $this->loadSpec('sections');
        // return [];
        // die;
        return self::loadSpec('~inheritance');
    }
}
