<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Test\Functional\ObjectSection\Alpha;
use Mustache\Test\Functional\ObjectSection\Beta;
use Mustache\Test\Functional\ObjectSection\Delta;
use Mustache\Test\Functional\ObjectSection\Gamma;
use PHPUnit\Framework\TestCase;

/**
 * @group sections
 * @group functional
 */
class ObjectSectionTest extends TestCase
{
    private Engine $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    public function testBasicObject(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#foo}}{{name}}{{/foo}}');
        $this->assertEquals('Foo', $tpl->render(new Alpha()));
    }

    /** @group magic_methods */
    public function testObjectWithGet(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#foo}}{{name}}{{/foo}}');
        $this->assertEquals('Foo', $tpl->render(new Beta()));
    }

    /** @group magic_methods */
    public function testSectionObjectWithGet(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#bar}}{{#foo}}{{name}}{{/foo}}{{/bar}}');
        $this->assertEquals('Foo', $tpl->render(new Gamma()));
    }

    public function testSectionObjectWithFunction(): void
    {
        $tpl = $this->mustache->loadTemplate('{{#foo}}{{name}}{{/foo}}');
        $alpha = new Alpha();
        $alpha->foo = new Delta();
        $this->assertEquals('Foo', $tpl->render($alpha));
    }
}
