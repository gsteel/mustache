<?php

declare(strict_types=1);

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Override;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[Group('lambdas')]
#[Group('functional')]
final class ClosureQuirksTest extends TestCase
{
    private Engine $mustache;

    #[Override]
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
