<?php

namespace Mustache\Test\FiveThree\Functional;

use DateTime;
use DateTimeZone;
use Mustache\Engine;
use PHPUnit\Framework\TestCase;

/**
 * @group pragmas
 * @group functional
 */
class Mustache_Test_FiveThree_Functional_EngineTest extends TestCase
{
    /**
     * @dataProvider pragmaData
     */
    public function testPragmasConstructorOption($pragmas, $helpers, $data, $tpl, $expect)
    {
        $mustache = new Engine(array(
            'pragmas' => $pragmas,
            'helpers' => $helpers,
        ));

        $this->assertEquals($expect, $mustache->render($tpl, $data));
    }

    public function pragmaData()
    {
        $helpers = array(
            'longdate' => function (\DateTime $value) {
                return $value->format('Y-m-d h:m:s');
            },
        );

        $data = array(
            'date' => new DateTime('1/1/2000', new DateTimeZone('UTC')),
        );

        $tpl = '{{ date | longdate }}';

        return array(
            array(array(Engine::PRAGMA_FILTERS), $helpers, $data, $tpl, '2000-01-01 12:01:00'),
            array(array(),                                $helpers, $data, $tpl, ''),
        );
    }
}
