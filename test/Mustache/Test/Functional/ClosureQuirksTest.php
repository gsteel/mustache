<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group lambdas
 * @group functional
 */
class ClosureQuirksTest extends TestCase
{
    private Engine $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine();
    }

    public function testClosuresDontLikeItWhenYouTouchTheirProperties(): void
    {
        $tpl = $this->mustache->loadTemplate('{{ foo.bar }}');
        $this->assertEquals('', $tpl->render([
            'foo' => static function () {
                return 'FOO';
            },
        ]));
    }
}
