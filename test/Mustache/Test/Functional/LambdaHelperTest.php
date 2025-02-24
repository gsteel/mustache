<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;
use stdClass;

use function strtoupper;

#[Group('lambdas')]
#[Group('functional')]
final class LambdaHelperTest extends TestCase
{
    private Engine $mustache;

    #[Override]
    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    public function testSectionLambdaHelper(): void
    {
        $one = $this->mustache->loadTemplate('{{name}}');
        $two = $this->mustache->loadTemplate('{{#lambda}}{{name}}{{/lambda}}');

        $foo = new stdClass();
        $foo->name = 'Mario';
        $foo->lambda = static function ($text, $mustache) {
            return strtoupper($mustache->render($text));
        };

        $this->assertEquals('Mario', $one->render($foo));
        $this->assertEquals('MARIO', $two->render($foo));
    }

    public function testSectionLambdaHelperRespectsDelimiterChanges(): void
    {
        $tpl = $this->mustache->loadTemplate("{{=<% %>=}}\n<%# bang %><% value %><%/ bang %>");

        $data = new stdClass();
        $data->value = 'hello world';
        $data->bang = static function ($text, $mustache) {
            return $mustache->render($text) . '!';
        };

        $this->assertEquals('hello world!', $tpl->render($data));
    }

    public function testLambdaHelperIsInvokable(): void
    {
        $one = $this->mustache->loadTemplate('{{name}}');
        $two = $this->mustache->loadTemplate('{{#lambda}}{{name}}{{/lambda}}');

        $foo = new stdClass();
        $foo->name = 'Mario';
        $foo->lambda = static function ($text, $render) {
            return strtoupper($render($text));
        };

        $this->assertEquals('Mario', $one->render($foo));
        $this->assertEquals('MARIO', $two->render($foo));
    }
}
