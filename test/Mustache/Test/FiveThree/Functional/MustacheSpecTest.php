<?php

namespace Mustache\Test\FiveThree\Functional;

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
     * @group lambdas
     * @dataProvider loadLambdasSpec
     */
    public function testLambdasSpec($desc, $source, $partials, $data, $expected)
    {
        $template = self::loadTemplate($source, $partials);
        $this->assertEquals($expected, $template($this->prepareLambdasSpec($data)), $desc);
    }

    public function loadLambdasSpec()
    {
        return $this->loadSpec('~lambdas');
    }

    /**
     * Extract and lambdafy any 'lambda' values found in the $data array.
     */
    private function prepareLambdasSpec($data)
    {
        foreach ($data as $key => $val) {
            if (isset($val['__tag__']) && $val['__tag__'] === 'code') {
                if (!isset($val['php'])) {
                    $this->markTestSkipped(sprintf('PHP lambda test not implemented for this test.'));
                    return;
                }

                $func = $val['php'];
                $data[$key] = function ($text = null) use ($func) {
                    return eval($func);
                };
            } elseif (is_array($val)) {
                $data[$key] = $this->prepareLambdasSpec($val);
            }
        }

        return $data;
    }
}
