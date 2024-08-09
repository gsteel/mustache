<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group lambdas
 * @group functional
 */
class PartialLambdaIndentTest extends TestCase
{
    public function testLambdasInsidePartialsAreIndentedProperly(): void
    {
        $src = <<<'EOS'
            <fieldset>
              {{> input }}
            </fieldset>
            
            EOS;
        $partial = <<<'EOS'
            <input placeholder="{{# upper }}Enter your name{{/ upper }}">
            
            EOS;

        $expected = <<<'EOS'
            <fieldset>
              <input placeholder="ENTER YOUR NAME">
            </fieldset>
            
            EOS;

        $m = new Engine([
            'partials' => ['input' => $partial],
        ]);

        $tpl = $m->loadTemplate($src);

        $data = new ClassWithLambda();
        $this->assertEquals($expected, $tpl->render($data));
    }

    public function testLambdaInterpolationsInsidePartialsAreIndentedProperly(): void
    {
        $src = <<<'EOS'
            <fieldset>
              {{> input }}
            </fieldset>
            
            EOS;
        $partial = <<<'EOS'
            <input placeholder="{{ placeholder }}">
            
            EOS;

        $expected = <<<'EOS'
            <fieldset>
              <input placeholder="Enter your name">
            </fieldset>
            
            EOS;

        $m = new Engine([
            'partials' => ['input' => $partial],
        ]);

        $tpl = $m->loadTemplate($src);

        $data = new ClassWithLambda();
        $this->assertEquals($expected, $tpl->render($data));
    }
}
