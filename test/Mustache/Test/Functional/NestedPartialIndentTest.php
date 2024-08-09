<?php

namespace Mustache\Test\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group functional
 * @group partials
 */
class NestedPartialIndentTest extends TestCase
{
    /**
     * @dataProvider partialsAndStuff
     */
    public function testNestedPartialsAreIndentedProperly($src, array $partials, $expected)
    {
        $m = new Engine(array(
            'partials' => $partials,
        ));
        $tpl = $m->loadTemplate($src);
        $this->assertEquals($expected, $tpl->render());
    }

    public function partialsAndStuff()
    {
        $partials = array(
            'a' => ' {{> b }}',
            'b' => ' {{> d }}',
            'c' => ' {{> d }}{{> d }}',
            'd' => 'D!',
        );

        return array(
            array(' {{> a }}', $partials, '   D!'),
            array(' {{> b }}', $partials, '  D!'),
            array(' {{> c }}', $partials, '  D!D!'),
        );
    }
}
