<?php

namespace Mustache\Test\FiveThree\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group lambdas
 * @group functional
 */
class ClosureQuirksTest extends TestCase
{
    private $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    public function testClosuresDontLikeItWhenYouTouchTheirProperties()
    {
        $tpl = $this->mustache->loadTemplate('{{ foo.bar }}');
        $this->assertEquals('', $tpl->render(['foo' => function () {
            return 'FOO';
        }]));
    }
}
