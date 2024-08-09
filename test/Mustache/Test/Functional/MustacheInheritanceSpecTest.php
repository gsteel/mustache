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
class MustacheInheritanceSpecTest extends SpecTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$mustache = new Engine(array(
          'pragmas' => array(Engine::PRAGMA_BLOCKS),
        ));
    }

    /**
     * @group inheritance
     * @dataProvider loadInheritanceSpec
     */
    public function testInheritanceSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadInheritanceSpec()
    {
        // return $this->loadSpec('sections');
        // return [];
        // die;
        return $this->loadSpec('~inheritance');
    }
}
