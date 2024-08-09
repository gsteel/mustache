<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Test\Functional\HigherOrderSections\Foo;
use Mustache\Test\Functional\HigherOrderSections\Monster;
use Mustache\Test\FunctionalTestCase;

use function file_exists;
use function get_class;
use function glob;
use function mkdir;
use function sprintf;

/**
 * @group lambdas
 * @group functional
 */
class HigherOrderSectionsTest extends FunctionalTestCase
{
    private Engine $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    /** @dataProvider sectionCallbackData */
    public function testSectionCallback(Foo $data, string $tpl, string $expect): void
    {
        $this->assertEquals($expect, $this->mustache->render($tpl, $data));
    }

    /** @return list<array{0: Foo, 1: string, 2: string}> */
    public static function sectionCallbackData(): array
    {
        $foo = new Foo();
        $foo->doublewrap = [$foo, 'wrapWithBoth'];

        $bar = new Foo();
        $bar->trimmer = [get_class($bar), 'staticTrim'];

        return [
            [$foo, '{{#doublewrap}}{{name}}{{/doublewrap}}', sprintf('<strong><em>%s</em></strong>', $foo->name)],
            [$bar, '{{#trimmer}}   {{name}}   {{/trimmer}}', $bar->name],
        ];
    }

    public function testSectionCallback2(): void
    {
        $one = $this->mustache->loadTemplate('{{name}}');
        $two = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $foo = new Foo();
        $foo->name = 'Luigi';

        $this->assertEquals($foo->name, $one->render($foo));
        $this->assertEquals(sprintf('<em>%s</em>', $foo->name), $two->render($foo));
    }

    public function testViewArraySectionCallback(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#trim}}    {{name}}    {{/trim}}');

        $foo = new Foo();

        $data = [
            'name' => 'Bob',
            'trim' => [get_class($foo), 'staticTrim'],
        ];

        $this->assertEquals($data['name'], $tpl->render($data));
    }

    public function testMonsters(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#title}}{{title}} {{/title}}{{name}}');

        $frank = new Monster();
        $frank->title = 'Dr.';
        $frank->name  = 'Frankenstein';
        $this->assertEquals('Dr. Frankenstein', $tpl->render($frank));

        $dracula = new Monster();
        $dracula->title = 'Count';
        $dracula->name  = 'Dracula';
        $this->assertEquals('Count Dracula', $tpl->render($dracula));
    }

    public function testPassThroughOptimization(): void
    {
        $builder = $this->getMockBuilder(Engine::class);
        $builder->onlyMethods(['loadLambda']);
        $mustache = $builder->getMock();
        $mustache->expects($this->never())
            ->method('loadLambda');

        $tpl = $mustache->loadTemplate('{{#wrap}}NAME{{/wrap}}');

        $foo = new Foo();
        $foo->wrap = [$foo, 'wrapWithEm'];

        $this->assertEquals('<em>NAME</em>', $tpl->render($foo));
    }

    public function testWithoutPassThroughOptimization(): void
    {
        $builder = $this->getMockBuilder(Engine::class);
        $builder->onlyMethods(['loadLambda']);
        $mustache = $builder->getMock();
        $mustache->expects(self::once())
            ->method('loadLambda')
            ->willReturn($mustache->loadTemplate('<em>{{ name }}</em>'));

        $tpl = $mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $foo = new Foo();
        $foo->wrap = [$foo, 'wrapWithEm'];

        $this->assertEquals('<em>' . $foo->name . '</em>', $tpl->render($foo));
    }

    /** @dataProvider cacheLambdaTemplatesData */
    public function testCacheLambdaTemplatesOptionWorks(
        string $dirName,
        string $tplPrefix,
        bool $enable,
        int $expect
    ): void {
        $cacheDir = $this->setUpCacheDir($dirName);
        $mustache = new Engine([
            'template_class_prefix'  => $tplPrefix,
            'cache'                  => $cacheDir,
            'cache_lambda_templates' => $enable,
        ]);

        $tpl = $mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');
        $foo = new Foo();
        $foo->wrap = [$foo, 'wrapWithEm'];
        $this->assertEquals('<em>' . $foo->name . '</em>', $tpl->render($foo));
        $this->assertCount($expect, glob($cacheDir . '/*.php'));
    }

    /** @return list<array{0: string, 1: string, 2: bool, 3: int}> */
    public static function cacheLambdaTemplatesData(): array
    {
        return [
            ['test_enabling_lambda_cache',  '_TestEnablingLambdaCache_',  true,  2],
            ['test_disabling_lambda_cache', '_TestDisablingLambdaCache_', false, 1],
        ];
    }

    private function setUpCacheDir(string $name): string
    {
        $cacheDir = self::$tempDir . '/' . $name;
        if (file_exists($cacheDir)) {
            self::rmdir($cacheDir);
        }

        mkdir($cacheDir, 0777, true);

        return $cacheDir;
    }

    public function testAnonymousFunctionSectionCallback(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#wrapper}}{{name}}{{/wrapper}}');

        $foo = new Foo();
        $foo->name = 'Mario';
        $foo->wrapper = static function (string $text): string {
            return sprintf('<div class="anonymous">%s</div>', $text);
        };

        $this->assertEquals(sprintf('<div class="anonymous">%s</div>', $foo->name), $tpl->render($foo));
    }

    public function testViewArrayAnonymousSectionCallback(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#wrap}}{{name}}{{/wrap}}');

        $data = [
            'name' => 'Bob',
            'wrap' => static function (string $text): string {
                return sprintf('[[%s]]', $text);
            },
        ];

        $this->assertEquals(sprintf('[[%s]]', $data['name']), $tpl->render($data));
    }
}
