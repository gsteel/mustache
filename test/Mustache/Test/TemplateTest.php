<?php

namespace Mustache\Test;

use Mustache\Context;
use Mustache\Engine;
use Mustache\Template;
use PHPUnit\Framework\TestCase;

class TemplateTest extends TestCase
{
    public function testConstructor()
    {
        $mustache = new Engine();
        $template = new Mustache_Test_TemplateStub($mustache);
        $this->assertSame($mustache, $template->getMustache());
    }

    public function testRendering()
    {
        $rendered = '<< wheee >>';
        $mustache = new Engine();
        $template = new Mustache_Test_TemplateStub($mustache);
        $template->rendered = $rendered;
        $context  = new Context();

        if (version_compare(PHP_VERSION, '5.3.0', '>=')) {
            $this->assertEquals($rendered, $template());
        }

        $this->assertEquals($rendered, $template->render());
        $this->assertEquals($rendered, $template->renderInternal($context));
        $this->assertEquals($rendered, $template->render(array('foo' => 'bar')));
    }
}

class Mustache_Test_TemplateStub extends Template
{
    public $rendered;

    public function getMustache()
    {
        return $this->mustache;
    }

    public function renderInternal(Context $context, $indent = '', $escape = false)
    {
        return $this->rendered;
    }
}
