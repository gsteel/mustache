<?php

namespace Mustache\Test\FiveThree\Functional;

use Mustache\Engine;
use PHPUnit\Framework\TestCase;
use stdClass;

/**
 * @group lambdas
 * @group functional
 */
class StrictCallablesTest extends TestCase
{
    /**
     * @dataProvider callables
     */
    public function testStrictCallables($strict, $name, $section, $expected)
    {
        $mustache = new Engine(array('strict_callables' => $strict));
        $tpl      = $mustache->loadTemplate('{{# section }}{{ name }}{{/ section }}');

        $data = new stdClass();
        $data->name    = $name;
        $data->section = $section;

        $this->assertEquals($expected, $tpl->render($data));
    }

    public function callables()
    {
        $lambda = function ($tpl, $mustache) {
            return strtoupper($mustache->render($tpl));
        };

        return array(
            // Interpolation lambdas
            array(
                false,
                array($this, 'instanceName'),
                $lambda,
                'YOSHI',
            ),
            array(
                false,
                array(__CLASS__, 'staticName'),
                $lambda,
                'YOSHI',
            ),
            array(
                false,
                function () {
                    return 'Yoshi';
                },
                $lambda,
                'YOSHI',
            ),

            // Section lambdas
            array(
                false,
                'Yoshi',
                array($this, 'instanceCallable'),
                'YOSHI',
            ),
            array(
                false,
                'Yoshi',
                array(__CLASS__, 'staticCallable'),
                'YOSHI',
            ),
            array(
                false,
                'Yoshi',
                $lambda,
                'YOSHI',
            ),

            // Strict interpolation lambdas
            array(
                true,
                function () {
                    return 'Yoshi';
                },
                $lambda,
                'YOSHI',
            ),

            // Strict section lambdas
            array(
                true,
                'Yoshi',
                array($this, 'instanceCallable'),
                'YoshiYoshi',
            ),
            array(
                true,
                'Yoshi',
                array(__CLASS__, 'staticCallable'),
                'YoshiYoshi',
            ),
            array(
                true,
                'Yoshi',
                function ($tpl, $mustache) {
                    return strtoupper($mustache->render($tpl));
                },
                'YOSHI',
            ),
        );
    }

    public function instanceCallable($tpl, $mustache)
    {
        return strtoupper($mustache->render($tpl));
    }

    public static function staticCallable($tpl, $mustache)
    {
        return strtoupper($mustache->render($tpl));
    }

    public function instanceName()
    {
        return 'Yoshi';
    }

    public static function staticName()
    {
        return 'Yoshi';
    }
}
