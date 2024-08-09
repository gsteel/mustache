<?php

namespace Mustache\Test\Functional;

use Mustache\Engine;
use Mustache\Exception\SyntaxException;
use PHPUnit\Framework\TestCase;

/**
 * @group dynamic-names
 * @group functional
 */
class DynamicPartialsTest extends TestCase
{
    private $mustache;

    protected function setUp(): void
    {
        $this->mustache = new Engine(array(
            'pragmas' => array(Engine::PRAGMA_DYNAMIC_NAMES),
        ));
    }

    public function getValidDynamicNamesExamples()
    {
      // technically not all dynamic names, but also not invalid
        return array(
            array('{{>* foo }}'),
            array('{{>* foo.bar.baz }}'),
            array('{{=* *=}}'),
            array('{{! *foo }}'),
            array('{{! foo.*bar }}'),
            array('{{% FILTERS }}{{! foo | *bar }}'),
            array('{{% BLOCKS }}{{< *foo }}{{/ *foo }}'),
        );
    }

    /**
     * @dataProvider getValidDynamicNamesExamples
     */
    public function testLegalInheritanceExamples($template)
    {
        $this->assertSame('', $this->mustache->render($template));
    }

    public function getDynamicNameParseErrors()
    {
        return array(
            array('{{# foo }}{{/ *foo }}'),
            array('{{^ foo }}{{/ *foo }}'),
            array('{{% BLOCKS }}{{< foo }}{{/ *foo }}'),
            array('{{% BLOCKS }}{{$ foo }}{{/ *foo }}'),
        );
    }

    /**
     * @dataProvider getDynamicNameParseErrors
     */
    public function testDynamicNameParseErrors($template)
    {
        $this->expectException(SyntaxException::class);
        $this->expectExceptionMessage('Nesting error:');
        $this->mustache->render($template);
    }


    public function testDynamicBlocks()
    {
        $tpl = '{{% BLOCKS }}{{< *partial }}{{$ bar }}{{ value }}{{/ bar }}{{/ *partial }}';

        $this->mustache->setPartials(array(
            'foobarbaz' => '{{% BLOCKS }}{{$ foo }}foo{{/ foo }}{{$ bar }}bar{{/ bar }}{{$ baz }}baz{{/ baz }}',
            'qux' => 'qux',
        ));

        $result = $this->mustache->render($tpl, array(
            'partial' => 'foobarbaz',
            'value' => 'BAR',
        ));

        $this->assertSame($result, 'fooBARbaz');
    }
}
