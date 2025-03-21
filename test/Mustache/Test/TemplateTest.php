<?php

declare(strict_types=1);

namespace Mustache\Test;

use Mustache\Context;
use Mustache\Engine;
use Mustache\Helper\ImmutableHelperCollection;
use PHPUnit\Framework\TestCase;

final class TemplateTest extends TestCase
{
    public function testConstructor(): void
    {
        $mustache = new Engine();
        $template = new TemplateStub($mustache, new ImmutableHelperCollection([]), true);
        $this->assertSame($mustache, $template->getMustache());
    }

    public function testRendering(): void
    {
        $rendered = '<< wheee >>';
        $mustache = new Engine();
        $template = new TemplateStub($mustache, new ImmutableHelperCollection([]), true);
        $template->rendered = $rendered;
        $context  = new Context();

        $this->assertEquals($rendered, $template());
        $this->assertEquals($rendered, $template->render());
        $this->assertEquals($rendered, $template->renderInternal($context));
        $this->assertEquals($rendered, $template->render(['foo' => 'bar']));
    }
}
