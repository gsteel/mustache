<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Test\SpecTestCase;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;

use function is_array;

/**
 * A PHPUnit test case wrapping the Mustache Spec.
 */
#[Group('mustache-spec')]
#[Group('functional')]
final class MustacheSpecTest extends SpecTestCase
{
    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     */
    #[DataProvider('loadCommentSpec')]
    #[Group('comments')]
    public function testCommentSpec(string $desc, string $source, array $partials, array $data, string $expected): void
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    /** @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}> */
    public static function loadCommentSpec(): array
    {
        return self::loadSpec('comments');
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     */
    #[DataProvider('loadDelimitersSpec')]
    #[Group('delimiters')]
    public function testDelimitersSpec(
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
    public static function loadDelimitersSpec(): array
    {
        return self::loadSpec('delimiters');
    }

    /** @param array<string, string> $partials */
    #[DataProvider('loadInterpolationSpec')]
    #[Group('interpolation')]
    public function testInterpolationSpec(
        string $desc,
        string $source,
        array $partials,
        mixed $data,
        string $expected,
    ): void {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    /** @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}> */
    public static function loadInterpolationSpec(): array
    {
        return self::loadSpec('interpolation');
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     */
    #[DataProvider('loadInvertedSpec')]
    #[Group('inverted')]
    #[Group('inverted-sections')]
    public function testInvertedSpec(string $desc, string $source, array $partials, array $data, string $expected): void
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    /** @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}> */
    public static function loadInvertedSpec(): array
    {
        return self::loadSpec('inverted');
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     */
    #[DataProvider('loadPartialsSpec')]
    #[Group('partials')]
    public function testPartialsSpec(string $desc, string $source, array $partials, array $data, string $expected): void
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    /** @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}> */
    public static function loadPartialsSpec(): array
    {
        return self::loadSpec('partials');
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     */
    #[DataProvider('loadSectionsSpec')]
    #[Group('sections')]
    public function testSectionsSpec(string $desc, string $source, array $partials, array $data, string $expected): void
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    /** @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}> */
    public static function loadSectionsSpec(): array
    {
        return self::loadSpec('sections');
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, mixed> $data
     */
    #[DataProvider('loadLambdasSpec')]
    #[Group('lambdas')]
    public function testLambdasSpec(string $desc, string $source, array $partials, array $data, string $expected): void
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template($this->prepareLambdasSpec($data)), $desc);
    }

    /** @return list<array{0: string, 1: string, 2: array<string, string>, 3: array<string, mixed>, 4: string}> */
    public static function loadLambdasSpec(): array
    {
        return self::loadSpec('~lambdas');
    }

    /**
     * Extract and lambdafy any 'lambda' values found in the $data array.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    private function prepareLambdasSpec(array $data): array|null
    {
        foreach ($data as $key => $val) {
            if (isset($val['__tag__']) && $val['__tag__'] === 'code') {
                if (! isset($val['php'])) {
                    $this->markTestSkipped('PHP lambda test not implemented for this test.');

                    return null;
                }

                $func = $val['php'];
                $data[$key] = static function (string|null $text = null) use ($func) {
                    return eval($func);
                };
            } elseif (is_array($val)) {
                $data[$key] = $this->prepareLambdasSpec($val);
            }
        }

        return $data;
    }
}
