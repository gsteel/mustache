<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Exception\SyntaxException;
use PHPUnit\Framework\TestCase;

/**
 * phpcs:disable Generic.Files.LineLength
 *
 * @group inheritance
 * @group functional
 */
class InheritanceTest extends TestCase
{
    private Engine $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine([
            'pragmas' => [Engine::PRAGMA_BLOCKS],
        ]);
    }

    /** @return list<array{0: array<string, string>, 1: array<string, string>, 2: string}> */
    public static function getIllegalInheritanceExamples(): array
    {
        return [
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [
                    'bar' => 'set by user',
                ],
                '{{< foo }}{{# bar }}{{$ baz }}{{/ baz }}{{/ bar }}{{/ foo }}',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [],
                '{{<foo}}{{^bar}}{{$baz}}set by template{{/baz}}{{/bar}}{{/foo}}',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                    'qux' => 'I am a partial',
                ],
                [],
                '{{<foo}}{{>qux}}{{$baz}}set by template{{/baz}}{{/foo}}',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [],
                '{{<foo}}{{=<% %>=}}<%={{ }}=%>{{/foo}}',
            ],
        ];
    }

    /** @return list<array{0: array<string, string>, 1: array<string, string>, 2: string, 3:string}> */
    public static function getLegalInheritanceExamples(): array
    {
        return [
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [
                    'bar' => 'set by user',
                ],
                '{{<foo}}{{bar}}{{$baz}}override{{/baz}}{{/foo}}',
                'override',
            ],
            [
                [
                    'foo' => '{{$baz}}default content{{/baz}}',
                ],
                [],
                '{{<foo}}{{! ignore me }}{{$baz}}set by template{{/baz}}{{/foo}}',
                'set by template',
            ],
            [
                [
                    'foo' => '{{$baz}}defualt content{{/baz}}',
                ],
                [],
                '{{<foo}}set by template{{$baz}}also set by template{{/baz}}{{/foo}}',
                'also set by template',
            ],
            [
                [
                    'foo' => '{{$a}}FAIL!{{/a}}',
                    'bar' => 'WIN!!',
                ],
                [],
                '{{<foo}}{{$a}}{{<bar}}FAIL{{/bar}}{{/a}}{{/foo}}',
                'WIN!!',
            ],
        ];
    }

    public function testDefaultContent(): void
    {
        $tpl = $this->mustache->loadTemplate('{{$title}}Default title{{/title}}');

        $data = [];

        $this->assertEquals('Default title', $tpl->render($data));
    }

    public function testDefaultContentRendersVariables(): void
    {
        $tpl = $this->mustache->loadTemplate('{{$foo}}default {{bar}} content{{/foo}}');

        $data = [
            'bar' => 'baz',
        ];

        $this->assertEquals('default baz content', $tpl->render($data));
    }

    public function testDefaultContentRendersTripleMustacheVariables(): void
    {
        $tpl = $this->mustache->loadTemplate('{{$foo}}default {{{bar}}} content{{/foo}}');

        $data = [
            'bar' => '<baz>',
        ];

        $this->assertEquals('default <baz> content', $tpl->render($data));
    }

    public function testDefaultContentRendersSections(): void
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{#bar}}{{baz}}{{/bar}} content{{/foo}}',
        );

        $data = [
            'bar' => ['baz' => 'qux'],
        ];

        $this->assertEquals('default qux content', $tpl->render($data));
    }

    public function testDefaultContentRendersNegativeSections(): void
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{^bar}}{{baz}}{{/bar}} content{{/foo}}',
        );

        $data = [
            'foo' => ['bar' => 'qux'],
            'baz' => 'three',
        ];

        $this->assertEquals('default three content', $tpl->render($data));
    }

    public function testMustacheInjectionInDefaultContent(): void
    {
        $tpl = $this->mustache->loadTemplate(
            '{{$foo}}default {{#bar}}{{baz}}{{/bar}} content{{/foo}}',
        );

        $data = [
            'bar' => ['baz' => '{{qux}}'],
        ];

        $this->assertEquals('default {{qux}} content', $tpl->render($data));
    }

    public function testDefaultContentRenderedInsideIncludedTemplates(): void
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{/include}}',
        );

        $data = [];

        $this->assertEquals('default content', $tpl->render($data));
    }

    public function testOverriddenContent(): void
    {
        $partials = [
            'super' => '...{{$title}}Default title{{/title}}...',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<super}}{{$title}}sub template title{{/title}}{{/super}}',
        );

        $data = [];

        $this->assertEquals('...sub template title...', $tpl->render($data));
    }

    public function testOverriddenPartial(): void
    {
        $partials = [
            'partial' => '|{{$stuff}}...{{/stuff}}{{$default}} default{{/default}}|',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'test {{<partial}}{{$stuff}}override1{{/stuff}}{{/partial}} {{<partial}}{{$stuff}}override2{{/stuff}}{{/partial}}',
        );

        $data = [];

        $this->assertEquals('test |override1 default| |override2 default|', $tpl->render($data));
    }

    public function testBlocksDoNotLeakBetweenPartials(): void
    {
        $partials = [
            'partial' => '|{{$a}}A{{/a}} {{$b}}B{{/b}}|',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'test {{<partial}}{{$a}}C{{/a}}{{/partial}} {{<partial}}{{$b}}D{{/b}}{{/partial}}',
        );

        $data = [];

        $this->assertEquals('test |C B| |A D|', $tpl->render($data));
    }

    public function testDataDoesNotOverrideBlock(): void
    {
        $partials = [
            'include' => '{{$var}}var in include{{/var}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{$var}}var in template{{/var}}{{/include}}',
        );

        $data = [
            'var' => 'var in data',
        ];

        $this->assertEquals('var in template', $tpl->render($data));
    }

    public function testDataDoesNotOverrideDefaultBlockValue(): void
    {
        $partials = [
            'include' => '{{$var}}var in include{{/var}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{/include}}',
        );

        $data = [
            'var' => 'var in data',
        ];

        $this->assertEquals('var in include', $tpl->render($data));
    }

    public function testOverridePartialWithNewlines(): void
    {
        $partials = [
            'partial' => '{{$ballmer}}peaking{{/ballmer}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            "{{<partial}}{{\$ballmer}}\npeaked\n\n:(\n{{/ballmer}}{{/partial}}",
        );

        $data = [];

        $this->assertEquals("peaked\n\n:(\n", $tpl->render($data));
    }

    public function testInheritIndentationWhenOverridingAPartial(): void
    {
        $partials = [
            'partial' => 'stop:
                    {{$nineties}}collaborate and listen{{/nineties}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<partial}}{{$nineties}}hammer time{{/nineties}}{{/partial}}',
        );

        $data = [];

        $this->assertEquals(
            'stop:
                    hammer time',
            $tpl->render($data),
        );
    }

    public function testInheritSpacingWhenOverridingAPartial(): void
    {
        $partials = [
            'parent' => 'collaborate_and{{$id}}{{/id}}',
            'child'  => '{{<parent}}{{$id}}_listen{{/id}}{{/parent}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            'stop:
              {{>child}}',
        );

        $data = [];

        $this->assertEquals(
            'stop:
              collaborate_and_listen',
            $tpl->render($data),
        );
    }

    public function testOverrideOneSubstitutionButNotTheOther(): void
    {
        $partials = [
            'partial' => '{{$stuff}}default one{{/stuff}}, {{$stuff2}}default two{{/stuff2}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<partial}}{{$stuff2}}override two{{/stuff2}}{{/partial}}',
        );

        $data = [];

        $this->assertEquals('default one, override two', $tpl->render($data));
    }

    public function testSuperTemplatesWithNoParameters(): void
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{>include}}|{{<include}}{{/include}}',
        );

        $data = [];

        $this->assertEquals('default content|default content', $tpl->render($data));
    }

    public function testRecursionInInheritedTemplates(): void
    {
        $partials = [
            'include'  => '{{$foo}}default content{{/foo}} {{$bar}}{{<include2}}{{/include2}}{{/bar}}',
            'include2' => '{{$foo}}include2 default content{{/foo}} {{<include}}{{$bar}}don\'t recurse{{/bar}}{{/include}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}}{{$foo}}override{{/foo}}{{/include}}',
        );

        $data = [];

        $this->assertEquals('override override override don\'t recurse', $tpl->render($data));
    }

    public function testTopLevelSubstitutionsTakePrecedenceInMultilevelInheritance(): void
    {
        $partials = [
            'parent'      => '{{<older}}{{$a}}p{{/a}}{{/older}}',
            'older'       => '{{<grandParent}}{{$a}}o{{/a}}{{/grandParent}}',
            'grandParent' => '{{$a}}g{{/a}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$a}}c{{/a}}{{/parent}}',
        );

        $data = [];

        $this->assertEquals('c', $tpl->render($data));
    }

    public function testMultiLevelInheritanceNoSubChild(): void
    {
        $partials = [
            'parent'      => '{{<older}}{{$a}}p{{/a}}{{/older}}',
            'older'       => '{{<grandParent}}{{$a}}o{{/a}}{{/grandParent}}',
            'grandParent' => '{{$a}}g{{/a}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{/parent}}',
        );

        $data = [];

        $this->assertEquals('p', $tpl->render($data));
    }

    public function testIgnoreTextInsideSuperTemplatesButParseArgs(): void
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}} asdfasd {{$foo}}hmm{{/foo}} asdfasdfasdf {{/include}}',
        );

        $data = [];

        $this->assertEquals('hmm', $tpl->render($data));
    }

    public function testIgnoreTextInsideSuperTemplates(): void
    {
        $partials = [
            'include' => '{{$foo}}default content{{/foo}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<include}} asdfasd asdfasdfasdf {{/include}}',
        );

        $data = [];

        $this->assertEquals('default content', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluation(): void
    {
        $partials = [
            'parent' => '{{#items}}{{$value}}ignored{{/value}}{{/items}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}{{$value}}<{{ . }}>{{/value}}{{/parent}}',
        );

        $data = ['items' => [1, 2, 3]];

        $this->assertEquals('<1><2><3>', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluationWhitespaceIgnored(): void
    {
        $partials = [
            'parent' => '{{#items}}{{$value}}\n\nignored\n\n{{/value}}{{/items}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}\n\n\n{{$value}}<{{ . }}>{{/value}}\n\n{{/parent}}',
        );

        $data = ['items' => [1, 2, 3]];

        $this->assertEquals('<1><2><3>', $tpl->render($data));
    }

    public function testInheritanceWithLazyEvaluationAndSections(): void
    {
        $partials = [
            'parent' => '{{#items}}{{$value}}\n\nignored {{.}} {{#more}} there is more {{/more}}\n\n{{/value}}{{/items}}',
        ];

        $this->mustache->setPartials($partials);

        $tpl = $this->mustache->loadTemplate(
            '{{<parent}}\n\n\n{{$value}}<{{ . }}>{{#more}} there is less {{/more}}{{/value}}\n\n{{/parent}}',
        );

        $data = ['items' => [1, 2, 3], 'more' => 'stuff'];

        $this->assertEquals('<1> there is less <2> there is less <3> there is less ', $tpl->render($data));
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, string> $data
     *
     * @dataProvider getIllegalInheritanceExamples
     */
    public function testIllegalInheritanceExamples(array $partials, array $data, string $template): void
    {
        $this->mustache->setPartials($partials);
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Illegal content in < parent tag');
        $tpl = $this->mustache->loadTemplate($template);
        $tpl->render($data);
    }

    /**
     * @param array<string, string> $partials
     * @param array<string, string> $data
     *
     * @dataProvider getLegalInheritanceExamples
     */
    public function testLegalInheritanceExamples(array $partials, array $data, string $template, string $expect): void
    {
        $this->mustache->setPartials($partials);
        $tpl = $this->mustache->loadTemplate($template);
        $this->assertSame($expect, $tpl->render($data));
    }
}
