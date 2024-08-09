<?php

namespace Mustache\Test\Functional;

use Mustache\Test\SpecTestCase;

/**
 * A PHPUnit test case wrapping the Mustache Spec.
 *
 * @group mustache-spec
 * @group functional
 */
class MustacheSpecTest extends SpecTestCase
{
    /**
     * @group comments
     * @dataProvider loadCommentSpec
     */
    public function testCommentSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadCommentSpec()
    {
        return $this->loadSpec('comments');
    }

    /**
     * @group delimiters
     * @dataProvider loadDelimitersSpec
     */
    public function testDelimitersSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadDelimitersSpec()
    {
        return $this->loadSpec('delimiters');
    }

    /**
     * @group interpolation
     * @dataProvider loadInterpolationSpec
     */
    public function testInterpolationSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadInterpolationSpec()
    {
        return $this->loadSpec('interpolation');
    }

    /**
     * @group inverted
     * @group inverted-sections
     * @dataProvider loadInvertedSpec
     */
    public function testInvertedSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadInvertedSpec()
    {
        return $this->loadSpec('inverted');
    }

    /**
     * @group partials
     * @dataProvider loadPartialsSpec
     */
    public function testPartialsSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadPartialsSpec()
    {
        return $this->loadSpec('partials');
    }

    /**
     * @group sections
     * @dataProvider loadSectionsSpec
     */
    public function testSectionsSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template->render($data), $desc);
    }

    public function loadSectionsSpec()
    {
        return $this->loadSpec('sections');
    }
}
