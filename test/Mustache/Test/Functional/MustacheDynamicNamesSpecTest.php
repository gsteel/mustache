<?php

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Test\SpecTestCase;

/**
 * A PHPUnit test case wrapping the Mustache Spec.
 *
 * @group mustache-spec
 * @group functional
 */
class MustacheDynamicNamesSpecTest extends SpecTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$mustache = new Engine(array(
          'pragmas' => array(Engine::PRAGMA_DYNAMIC_NAMES),
        ));
    }

    /**
     * @group dynamic-names
     * @dataProvider loadDynamicNamesSpec
     */
    public function testDynamicNamesSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadDynamicNamesSpec()
    {
        return $this->loadSpec('~dynamic-names');
    }
}
