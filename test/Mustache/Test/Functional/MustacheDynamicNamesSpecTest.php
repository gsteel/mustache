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
class MustacheDynamicNamesSpecTest extends SpecTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$mustache = new Engine([
            'pragmas' => [Engine::PRAGMA_DYNAMIC_NAMES],
        ]);
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     */
    #[DataProvider('loadDynamicNamesSpec')]
    #[Group('dynamic-names')]
    public function testDynamicNamesSpec(
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
    public static function loadDynamicNamesSpec(): array
    {
        return self::loadSpec('~dynamic-names');
    }
}
