<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Exception\SyntaxException;
use Mustache\Loader\StringLoader;
use PHPUnit\Framework\TestCase;

/**
 * @group dynamic-names
 * @group functional
 */
class DynamicPartialsTest extends TestCase
{
    private Engine $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine([
            'pragmas' => [Engine::PRAGMA_DYNAMIC_NAMES],
            'loader' => new StringLoader(),
        ]);
    }

    /** @return list<array{0: string}> */
    public static function getValidDynamicNamesExamples(): array
    {
      // technically not all dynamic names, but also not invalid
        return [
            ['{{>* foo }}'],
            ['{{>* foo.bar.baz }}'],
            ['{{=* *=}}'],
            ['{{! *foo }}'],
            ['{{! foo.*bar }}'],
            ['{{% FILTERS }}{{! foo | *bar }}'],
            ['{{% BLOCKS }}{{< *foo }}{{/ *foo }}'],
        ];
    }

    /** @dataProvider getValidDynamicNamesExamples */
    public function testLegalInheritanceExamples(string $template): void
    {
        $this->assertSame('', $this->mustache->render($template));
    }

    /** @return list<array{0: string}> */
    public static function getDynamicNameParseErrors(): array
    {
        return [
            ['{{# foo }}{{/ *foo }}'],
            ['{{^ foo }}{{/ *foo }}'],
            ['{{% BLOCKS }}{{< foo }}{{/ *foo }}'],
            ['{{% BLOCKS }}{{$ foo }}{{/ *foo }}'],
        ];
    }

    /** @dataProvider getDynamicNameParseErrors */
    public function testDynamicNameParseErrors(string $template): void
    {
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Nesting error:');
        $this->mustache->render($template);
    }

    public function testDynamicBlocks(): void
    {
        $tpl = '{{% BLOCKS }}{{< *partial }}{{$ bar }}{{ value }}{{/ bar }}{{/ *partial }}';

        $this->mustache->setPartials([
            'foobarbaz' => '{{% BLOCKS }}{{$ foo }}foo{{/ foo }}{{$ bar }}bar{{/ bar }}{{$ baz }}baz{{/ baz }}',
            'qux' => 'qux',
        ]);

        $result = $this->mustache->render($tpl, [
            'partial' => 'foobarbaz',
            'value' => 'BAR',
        ]);

        $this->assertSame($result, 'fooBARbaz');
    }
}
