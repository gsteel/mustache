<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Test\SpecTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

/**
 * A PHPUnit test case wrapping the Mustache Spec.
 */
#[Group('mustache-spec')]
#[Group('functional')]
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
     */
    #[DataProvider('loadInheritanceSpec')]
    #[Group('inheritance')]
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
