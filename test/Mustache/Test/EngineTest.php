<?php

declare(strict_types=1);

namespace Mustache\Test;

use DateTime;
use DateTimeZone;
use Monolog\Handler\StreamHandler;
use Monolog\Logger;
use Monolog\Processor\PsrLogMessageProcessor;
use Mustache\Cache\FilesystemCache;
use Mustache\Cache\NoopCache;
use Mustache\Engine;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\RuntimeException;
use Mustache\Loader\ArrayLoader;
use Mustache\Loader\ProductionFilesystemLoader;
use Mustache\Loader\StringLoader;
use Mustache\Template;
use Mustache\Test\Asset\EngineStub;
use Psr\Log\LogLevel;
use Psr\Log\NullLogger;

use function dirname;
use function file_get_contents;
use function realpath;
use function sys_get_temp_dir;
use function tempnam;

use const ENT_QUOTES;

class EngineTest extends FunctionalTestCase
{
    public function testConstructor(): void
    {
        $logger         = new NullLogger();
        $loader         = new StringLoader();
        $partialsLoader = new ArrayLoader();
        $mustache       = new Engine([
            'template_class_prefix' => '__whot__',
            'logger'                => $logger,
            'loader'                => $loader,
            'partials_loader'       => $partialsLoader,
            'partials'              => [
                'foo' => '{{ foo }}',
            ],
            'helpers' => [
                'foo' => [$this, 'getFoo'],
                'bar' => 'BAR',
            ],
            'escape'       => 'strtoupper',
            'entity_flags' => ENT_QUOTES,
            'charset'      => 'ISO-8859-1',
            'pragmas'      => [Engine::PRAGMA_FILTERS],
        ]);

        $this->assertEquals('{{ foo }}', $partialsLoader->load('foo'));
        $this->assertStringContainsString('__whot__', $mustache->getTemplateClassName('{{ foo }}'));
        $this->assertEquals('strtoupper', $mustache->getEscape());
        $this->assertEquals('ISO-8859-1', $mustache->getCharset());
        $this->assertTrue($mustache->hasHelper('foo'));
        $this->assertTrue($mustache->hasHelper('bar'));
        $this->assertFalse($mustache->hasHelper('baz'));
    }

    public static function getFoo(): string
    {
        return 'foo';
    }

    public function testRender(): void
    {
        $source = '{{ foo }}';
        $data   = ['bar' => 'baz'];
        $output = 'TEH OUTPUT';

        $template = $this->getMockBuilder(Template::class)
            ->disableOriginalConstructor()
            ->getMock();

        $mustache = new EngineStub();
        $mustache->template = $template;

        $template->expects($this->once())
            ->method('render')
            ->with($data)
            ->willReturn($output);

        $this->assertEquals($output, $mustache->render($source, $data));
        $this->assertEquals($source, $mustache->source);
    }

    /** @group functional */
    public function testCache(): void
    {
        $mustache = new Engine([
            'template_class_prefix' => '__whot__',
            'cache'                 => new FilesystemCache(self::$tempDir),
        ]);

        $source    = '{{ foo }}';
        $template  = $mustache->loadTemplate($source);
        $className = $mustache->getTemplateClassName($source);

        $this->assertInstanceOf($className, $template);
    }

    public function testLambdaCache(): void
    {
        $cache = new NoopCache();
        $mustache = new EngineStub([
            'cache'                  => $cache,
            'cache_lambda_templates' => true,
        ]);

        $this->assertSame($cache, $mustache->getProtectedLambdaCache());
    }

    public function testWithoutLambdaCache(): void
    {
        $cache = new NoopCache();
        $mustache = new EngineStub([
            'cache' => $cache,
        ]);

        $this->assertInstanceOf(NoopCache::class, $mustache->getProtectedLambdaCache());
        $this->assertNotSame($cache, $mustache->getProtectedLambdaCache());
    }

    public function testEmptyTemplatePrefixThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /** @psalm-suppress InvalidArgument */
        new Engine([
            'template_class_prefix' => '',
        ]);
    }

    /** @dataProvider getBadEscapers */
    public function testNonCallableEscapeThrowsException(mixed $escape): void
    {
        $this->expectException(InvalidArgumentException::class);
        /** @psalm-suppress MixedArgumentTypeCoercion */
        new Engine(['escape' => $escape]);
    }

    /** @return list<array{0: mixed}> */
    public static function getBadEscapers(): array
    {
        return [
            ['nothing'],
            [['foo', 'bar']],
        ];
    }

    public function testImmutablePartialsLoadersThrowException(): void
    {
        $mustache = new Engine([
            'partials_loader' => new StringLoader(),
        ]);
        $this->expectException(RuntimeException::class);
        $mustache->setPartials(['foo' => '{{ foo }}']);
    }

    public function testMissingPartialsTreatedAsEmptyString(): void
    {
        $mustache = new Engine([
            'partials_loader' => new ArrayLoader([
                'foo' => 'FOO',
                'baz' => 'BAZ',
            ]),
        ]);

        $this->assertEquals('FOOBAZ', $mustache->render('{{>foo}}{{>bar}}{{>baz}}', []));
    }

    public function testHelpers(): void
    {
        $foo = [$this, 'getFoo'];
        $bar = 'BAR';
        $mustache = new Engine([
            'helpers' => [
                'foo' => $foo,
                'bar' => $bar,
            ],
            'strict_callables' => false,
        ]);

        $helpers = $mustache->getHelpers();
        $this->assertTrue($mustache->hasHelper('foo'));
        $this->assertTrue($mustache->hasHelper('bar'));
        $this->assertTrue($helpers->has('foo'));
        $this->assertTrue($helpers->has('bar'));
        $this->assertSame($foo, $mustache->getHelper('foo'));
        $this->assertSame($bar, $mustache->getHelper('bar'));

        $mustache->removeHelper('bar');
        $this->assertFalse($mustache->hasHelper('bar'));
        $mustache->addHelper('bar', $bar);
        $this->assertSame($bar, $mustache->getHelper('bar'));

        $baz = [$this, 'wrapWithUnderscores'];
        $this->assertFalse($mustache->hasHelper('baz'));
        $this->assertFalse($helpers->has('baz'));

        $mustache->addHelper('baz', $baz);
        $this->assertTrue($mustache->hasHelper('baz'));
        $this->assertTrue($helpers->has('baz'));

        // ... and a functional test
        $tpl = $mustache->loadTemplate('{{foo}} - {{bar}} - {{#baz}}qux{{/baz}}');
        $this->assertEquals('foo - BAR - __qux__', $tpl->render());
        $this->assertEquals('foo - BAR - __qux__', $tpl->render(['qux' => "won't mess things up"]));
    }

    public static function wrapWithUnderscores(string $text): string
    {
        return '__' . $text . '__';
    }

    public function testLoadPartialCascading(): void
    {
        $loader = new ArrayLoader([
            'foo' => 'FOO',
        ]);

        $mustache = new Engine(['loader' => $loader]);

        $tpl = $mustache->loadTemplate('foo');

        $this->assertSame($tpl, $mustache->loadPartial('foo'));

        $mustache->setPartials([
            'foo' => 'f00',
        ]);

        // setting partials overrides the default template loading fallback.
        $this->assertNotSame($tpl, $mustache->loadPartial('foo'));

        // but it didn't overwrite the original template loader templates.
        $this->assertSame($tpl, $mustache->loadTemplate('foo'));
    }

    public function testPartialLoadFailLogging(): void
    {
        $name     = tempnam(sys_get_temp_dir(), 'mustache-test');
        $mustache = new Engine([
            'logger' => new Logger(
                'log',
                [new StreamHandler($name, LogLevel::WARNING)],
                [new PsrLogMessageProcessor()],
            ),
            'partials' => [
                'foo' => 'FOO',
                'bar' => 'BAR',
            ],
        ]);

        $result = $mustache->render('{{> foo }}{{> bar }}{{> baz }}', []);
        $this->assertEquals('FOOBAR', $result);

        $this->assertStringContainsString('WARNING: Partial not found: "baz"', file_get_contents($name));
    }

    public function testCacheWarningLogging(): void
    {
        [$name, $mustache] = $this->getLoggedMustache(LogLevel::WARNING);
        $mustache->render('{{ foo }}', ['foo' => 'FOO']);
        $this->assertStringContainsString('WARNING: Template cache disabled, evaluating', file_get_contents($name));
    }

    public function testLoggingIsNotTooAnnoying(): void
    {
        [$name, $mustache] = $this->getLoggedMustache();
        $mustache->render('{{ foo }}{{> bar }}', ['foo' => 'FOO']);
        $this->assertEmpty(file_get_contents($name));
    }

    public function testVerboseLoggingIsVerbose(): void
    {
        [$name, $mustache] = $this->getLoggedMustache(LogLevel::DEBUG);
        $mustache->render('{{ foo }}{{> bar }}', ['foo' => 'FOO']);
        $log = file_get_contents($name);
        $this->assertStringContainsString('DEBUG: Instantiating template: ', $log);
        $this->assertStringContainsString('WARNING: Partial not found: "bar"', $log);
    }

    public function testUnknownPragmaThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        /** @psalm-suppress InvalidArgument */
        new Engine([
            'pragmas' => ['UNKNOWN'],
        ]);
    }

    public function testCompileFromMustacheSourceInstance(): void
    {
        $baseDir = realpath(dirname(__FILE__) . '/../../fixtures/templates');
        $mustache = new Engine([
            'loader' => new ProductionFilesystemLoader($baseDir),
        ]);
        $this->assertEquals('one contents', $mustache->render('one'));
    }

    /** @return array{0: string, 1: Engine} */
    private function getLoggedMustache(string $level = LogLevel::ERROR): array
    {
        $name     = tempnam(sys_get_temp_dir(), 'mustache-test');
        $mustache = new Engine([
            'logger' => new Logger('log', [new StreamHandler($name, $level)], [new PsrLogMessageProcessor()]),
        ]);

        return [$name, $mustache];
    }

    public function testCustomDelimiters(): void
    {
        $mustache = new Engine([
            'delimiters' => '[[ ]]',
            'partials'   => [
                'one' => '[[> two ]]',
                'two' => '[[ a ]]',
            ],
        ]);

        $tpl = $mustache->loadTemplate('[[# a ]][[ b ]][[/a ]]');
        $this->assertEquals('c', $tpl->render(['a' => true, 'b' => 'c']));

        $tpl = $mustache->loadTemplate('[[> one ]]');
        $this->assertEquals('b', $tpl->render(['a' => 'b']));
    }

    public function testBuggyPropertyShadowing(): void
    {
        $mustache = new Engine();
        $this->assertFalse($mustache->useBuggyPropertyShadowing());

        $mustache = new Engine(['buggy_property_shadowing' => true]);
        $this->assertTrue($mustache->useBuggyPropertyShadowing());
    }

    /**
     * @param list<Engine::PRAGMA_*> $pragmas
     * @param array<string, mixed> $helpers
     * @param array<string, mixed> $data
     *
     * @dataProvider pragmaData
     */
    public function testPragmasConstructorOption(
        array $pragmas,
        array $helpers,
        array $data,
        string $tpl,
        string $expect,
    ): void {
        $mustache = new Engine([
            'pragmas' => $pragmas,
            'helpers' => $helpers,
        ]);

        $this->assertEquals($expect, $mustache->render($tpl, $data));
    }

    /**
     * @return list<array{
     *     0: list<Engine::PRAGMA_*>,
     *     1: array<string, mixed>,
     *     2: array<string, mixed>,
     *     3: string,
     *     4: string,
     * }>
     */
    public static function pragmaData(): array
    {
        $helpers = [
            'longdate' => static function (DateTime $value): string {
                return $value->format('Y-m-d h:m:s');
            },
        ];

        $data = [
            'date' => new DateTime('1/1/2000', new DateTimeZone('UTC')),
        ];

        $tpl = '{{ date | longdate }}';

        return [
            [[Engine::PRAGMA_FILTERS], $helpers, $data, $tpl, '2000-01-01 12:01:00'],
            [[], $helpers, $data, $tpl, ''],
        ];
    }
}
