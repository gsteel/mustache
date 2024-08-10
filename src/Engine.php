<?php

declare(strict_types=1);

namespace Mustache;

use Mustache\Cache\AbstractCache;
use Mustache\Cache\NoopCache;
use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader\ArrayLoader;
use Mustache\Loader\MutableLoader;
use Mustache\Loader\StringLoader;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;

use function array_key_exists;
use function array_keys;
use function class_exists;
use function is_callable;
use function json_encode;
use function md5;
use function sprintf;

use const ENT_HTML401;
use const ENT_QUOTES;
use const ENT_SUBSTITUTE;

/**
 * A Mustache implementation in PHP.
 *
 * {@link http://defunkt.github.com/mustache}
 *
 * Mustache is a framework-agnostic logic-less templating language. It enforces separation of view
 * logic from template files. In fact, it is not even possible to embed logic in the template.
 *
 * This is very, very rad.
 *
 * @psalm-type Options = array{
 *     cache?: Cache,
 *     template_class_prefix?: non-empty-string,
 *     cache_lambda_templates?: bool,
 *     delimiters?: string,
 *     loader?: Loader,
 *     partials_loader?: Loader,
 *     partials?: array<string, string>,
 *     helpers?: iterable<string, mixed>|HelperCollection,
 *     escape?: callable(string): string,
 *     entity_flags?: int,
 *     charset?: string,
 *     logger?: LoggerInterface,
 *     strict_callables?: bool,
 *     pragmas?: list<Engine::PRAGMA_*>,
 * }
 */
class Engine
{
    public const VERSION = '2.14.2';
    public const SPEC_VERSION = '1.3.0';
    public const PRAGMA_FILTERS = 'FILTERS';
    public const PRAGMA_BLOCKS = 'BLOCKS';
    public const PRAGMA_ANCHORED_DOT = 'ANCHORED-DOT';
    public const PRAGMA_DYNAMIC_NAMES = 'DYNAMIC-NAMES';
    private const KNOWN_PRAGMAS = [
        self::PRAGMA_FILTERS => true,
        self::PRAGMA_BLOCKS => true,
        self::PRAGMA_ANCHORED_DOT => true,
        self::PRAGMA_DYNAMIC_NAMES => true,
    ];
    // Mustache\Template cache
    /** @var array<class-string<Template>, Template> */
    private array $templates = [];
    // Environment
    private string $templateClassPrefix = '__Mustache_';
    private readonly Cache $cache;
    private Cache|null $lambdaCache = null;
    private bool $cacheLambdaTemplates = false;
    private readonly Loader $loader;
    private Loader|null $partialsLoader;
    private HelperCollection $helpers;
    /** @var callable */
    private $escape;
    private readonly int $entityFlags;
    private string $charset = 'UTF-8';
    private readonly LoggerInterface|null $logger;
    private readonly bool $strictCallables;
    /** @var array<self::PRAGMA_*, bool> */
    private array $pragmas = [];
    private string|null $delimiters = null;
    // Services
    private readonly Tokenizer $tokenizer;
    private readonly Parser $parser;
    private readonly Compiler $compiler;

    /**
     * Mustache class constructor.
     *
     * Passing an $options array allows overriding certain Mustache options during instantiation:
     *
     *     $options = array(
     *         // The class prefix for compiled templates. Defaults to '__Mustache_'.
     *         'template_class_prefix' => '__MyTemplates_',
     *
     *         // A Mustache cache instance or a cache directory string for compiled templates.
     *         // Mustache will not cache templates unless this is set.
     *         'cache' => dirname(__FILE__).'/tmp/cache/mustache',
     *
     *         // Optionally, enable caching for lambda section templates. This is generally not recommended, as lambda
     *         // sections are often too dynamic to benefit from caching.
     *         'cache_lambda_templates' => true,
     *
     *         // Customize the tag delimiters used by this engine instance. Note that overriding here changes the
     *         // delimiters used to parse all templates and partials loaded by this instance. To override just for a
     *         // single template, use an inline "change delimiters" tag at the start of the template file:
     *         //
     *         //     {{=<% %>=}}
     *         //
     *         'delimiters' => '<% %>',
     *
     *         // A Mustache template loader instance. Uses a StringLoader if not specified.
     *         'loader' => new Mustache\Loader\FilesystemLoader(dirname(__FILE__).'/views'),
     *
     *         // A Mustache loader instance for partials.
     *         'partials_loader' => new Mustache\Loader\FilesystemLoader(dirname(__FILE__).'/views/partials'),
     *
     *         // An array of Mustache partials. Useful for quick-and-dirty string template loading, but not as
     *         // efficient or lazy as a Filesystem (or database) loader.
     *         'partials' => array('foo' => file_get_contents(dirname(__FILE__).'/views/partials/foo.mustache')),
     *
     *         // An array of 'helpers'. Helpers can be global variables or objects, closures (e.g. for higher order
     *         // sections), or any other valid Mustache context value. They will be prepended to the context stack,
     *         // so they will be available in any template loaded by this Mustache instance.
     *         'helpers' => array('i18n' => function ($text) {
     *             // do something translatey here...
     *         }),
     *
     *         // An 'escape' callback, responsible for escaping double-mustache variables.
     *         'escape' => function ($value) {
     *             return htmlspecialchars($buffer, ENT_COMPAT, 'UTF-8');
     *         },
     *
     *         // Type argument for `htmlspecialchars`.  Defaults to ENT_COMPAT.  You may prefer ENT_QUOTES.
     *         'entity_flags' => ENT_QUOTES,
     *
     *         // Character set for `htmlspecialchars`. Defaults to 'UTF-8'. Use 'UTF-8'.
     *         'charset' => 'ISO-8859-1',
     *
     *         // A Psr Logger instance. No logging will occur unless this is set. Using a PSR-3 compatible
     *         // logging library -- such as Monolog -- is highly recommended
     *         'logger' => $loggerInstance,
     *
     *         // Only treat Closure instances and invokable classes as callable. If true, values like
     *         // `array('ClassName', 'methodName')` and `array($classInstance, 'methodName')`, which are traditionally
     *         // "callable" in PHP, are not called to resolve variables for interpolation or section contexts. This
     *         // helps protect against arbitrary code execution when user input is passed directly into the template.
     *         // This currently defaults to false, but will default to true in v3.0.
     *         'strict_callables' => true,
     *
     *         // Enable pragmas across all templates, regardless of the presence of pragma tags in the individual
     *         // templates.
     *         'pragmas' => [Mustache\Engine::PRAGMA_FILTERS],
     *     );
     *
     * @param Options $options
     *
     * @throws InvalidArgumentException If `escape` option is not callable.
     */
    public function __construct(array $options = [])
    {
        $this->tokenizer = new Tokenizer();
        $this->parser = new Parser();
        $this->compiler = new Compiler();

        if (isset($options['template_class_prefix'])) {
            /** @psalm-suppress DocblockTypeContradiction - Defensive Check */
            if ($options['template_class_prefix'] === '') {
                throw new InvalidArgumentException('Mustache Constructor "template_class_prefix" must not be empty');
            }

            $this->templateClassPrefix = $options['template_class_prefix'];
        }

        $this->cache = isset($options['cache']) && $options['cache'] instanceof Cache
            ? $options['cache']
            : new NoopCache();

        $this->logger = $options['logger'] ?? null;

        if ($this->cache instanceof AbstractCache && $this->cache->getLogger() === null) {
            $this->cache->setLogger($this->logger);
        }

        $this->cacheLambdaTemplates = $options['cache_lambda_templates'] ?? false;

        $this->loader = $options['loader'] ?? new StringLoader();
        $this->partialsLoader = $options['partials_loader'] ?? null;

        if (isset($options['partials'])) {
            $this->setPartials($options['partials']);
        }

        $helpersOption = $options['helpers'] ?? [];
        $this->helpers = $helpersOption instanceof HelperCollection
            ? $helpersOption
            : new HelperCollection($helpersOption);

        if (isset($options['escape'])) {
            if (! is_callable($options['escape'])) {
                throw new InvalidArgumentException('Mustache Constructor "escape" option must be callable');
            }

            $this->escape = $options['escape'];
        }

        $this->entityFlags = $options['entity_flags'] ?? ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML401;

        if (isset($options['charset'])) {
            $this->charset = $options['charset'];
        }

        $this->strictCallables = $options['strict_callables'] ?? true;

        if (isset($options['delimiters'])) {
            $this->delimiters = $options['delimiters'];
        }

        $pragmas = $options['pragmas'] ?? [];
        foreach ($pragmas as $pragma) {
            if (! array_key_exists($pragma, self::KNOWN_PRAGMAS)) {
                throw new InvalidArgumentException(sprintf('Unknown pragma: "%s".', $pragma));
            }

            $this->pragmas[$pragma] = true;
        }
    }

    /**
     * Shortcut 'render' invocation.
     *
     * Equivalent to calling `$mustache->loadTemplate($template)->render($context);`
     *
     * @see Template::render
     * @see Engine::loadTemplate
     *
     * @param mixed $context (default: array())
     *
     * @return string Rendered template
     */
    public function render(string $template, mixed $context = []): string
    {
        return $this->loadTemplate($template)->render($context);
    }

    /**
     * Get the current Mustache escape callback.
     */
    public function getEscape(): callable|null
    {
        return $this->escape;
    }

    /**
     * Get the current Mustache character set.
     */
    public function getCharset(): string
    {
        return $this->charset;
    }

    /**
     * Get the current globally enabled pragmas.
     *
     * @return list<self::PRAGMA_*>
     */
    private function getPragmas(): array
    {
        return array_keys($this->pragmas);
    }

    /**
     * Get the current Mustache partials Loader instance.
     *
     * If no Loader instance has been explicitly specified, this method will instantiate and return
     * an ArrayLoader instance.
     */
    private function getPartialsLoader(): Loader
    {
        if ($this->partialsLoader === null) {
            $this->partialsLoader = new ArrayLoader();
        }

        return $this->partialsLoader;
    }

    /**
     * Set partials for the current partials Loader instance.
     *
     * @param array<string, string> $partials (default: array())
     *
     * @throws RuntimeException If the current Mustache\Loader instance is immutable.
     */
    public function setPartials(array $partials = []): void
    {
        $loader = $this->getPartialsLoader();
        if (! $loader instanceof MutableLoader) {
            throw new RuntimeException('Unable to set partials on an immutable \Mustache\Loader instance');
        }

        $loader->setTemplates($partials);
    }

    /**
     * Get the current Lambda Cache instance.
     *
     * If 'cache_lambda_templates' is enabled, this is the default cache instance. Otherwise, it is a NoopCache.
     */
    protected function getLambdaCache(): Cache
    {
        if ($this->cacheLambdaTemplates) {
            return $this->cache;
        }

        if (! isset($this->lambdaCache)) {
            $this->lambdaCache = new NoopCache();
        }

        return $this->lambdaCache;
    }

    /**
     * Helper method to generate a Mustache template class.
     *
     * This method must be updated any time options are added which make it so
     * the same template could be parsed and compiled multiple different ways.
     *
     * @return string Mustache Template class name
     */
    public function getTemplateClassName(string|Source $source): string
    {
        // For the most part, adding a new option here should do the trick.
        //
        // Pick a value here which is unique for each possible way the template
        // could be compiled... but not necessarily unique per option value. See
        // escape below, which only needs to differentiate between 'custom' and
        // 'default' escapes.
        //
        // Keep this list in alphabetical order :)
        $chunks = [
            'charset' => $this->charset,
            'delimiters' => $this->delimiters ?? '{{ }}',
            'entityFlags' => $this->entityFlags,
            'escape' => isset($this->escape) ? 'custom' : 'default',
            'key' => $source instanceof Source ? $source->getKey() : 'source',
            'pragmas' => $this->getPragmas(),
            'strictCallables' => $this->strictCallables,
            'version' => self::VERSION,
        ];

        $key = json_encode($chunks);

        // Template Source instances have already provided their own source key. For strings, just include the whole
        // source string in the md5 hash.
        if (! $source instanceof Source) {
            $key .= "\n" . $source;
        }

        return $this->templateClassPrefix . md5($key);
    }

    /**
     * Load a Mustache Template by name.
     */
    public function loadTemplate(string $name): Template
    {
        return $this->loadSource($this->loader->load($name));
    }

    /**
     * Load a Mustache partial Template by name.
     *
     * This is a helper method used internally by Template instances for loading partial templates. You can most likely
     * ignore it completely.
     */
    public function loadPartial(string $name): Template|null
    {
        try {
            $loader = $this->partialsLoader;
            if ($loader === null && ! $this->loader instanceof StringLoader) {
                $loader = $this->loader;
            }

            if ($loader === null) {
                throw new UnknownTemplateException($name);
            }

            return $this->loadSource($loader->load($name));
        } catch (UnknownTemplateException $e) {
            // If the named partial cannot be found, log then return null.
            $this->log(
                LogLevel::WARNING,
                'Partial not found: "{name}"',
                ['name' => $e->getTemplateName()],
            );
        }

        return null;
    }

    /**
     * Load a Mustache lambda Template by source.
     *
     * This is a helper method used by Template instances to generate subtemplates for Lambda sections. You can most
     * likely ignore it completely.
     */
    public function loadLambda(string $source, string|null $delims = null): Template
    {
        if ($delims !== null) {
            $source = $delims . "\n" . $source;
        }

        return $this->loadSource($source, $this->getLambdaCache());
    }

    /**
     * Instantiate and return a Mustache Template instance by source.
     *
     * Optionally provide a Mustache\Cache instance. This is used internally by Mustache\Engine::loadLambda to respect
     * the 'cache_lambda_templates' configuration option.
     *
     * @see Engine::loadTemplate
     * @see Engine::loadPartial
     * @see Engine::loadLambda
     */
    private function loadSource(string|Source $source, Cache|null $cache = null): Template
    {
        /** @psalm-var class-string<Template> $className */
        $className = $this->getTemplateClassName($source);

        if (! isset($this->templates[$className])) {
            $cache ??= $this->cache;

            if (! class_exists($className, false)) {
                if (! $cache->load($className)) {
                    $compiled = $this->compile($source);
                    $cache->cache($className, $compiled);
                }
            }

            $this->log(
                LogLevel::DEBUG,
                'Instantiating template: "{className}"',
                ['className' => $className],
            );

            $this->templates[$className] = new $className(
                $this,
                $this->helpers,
                $this->strictCallables,
            );
        }

        return $this->templates[$className];
    }

    /**
     * Helper method to tokenize a Mustache template.
     *
     * @see Tokenizer::scan
     *
     * @return list<array<string, mixed>> Tokens
     */
    private function tokenize(string $source): array
    {
        return $this->tokenizer->scan($source, $this->delimiters);
    }

    /**
     * Helper method to parse a Mustache template.
     *
     * @see Parser::parse
     *
     * @return list<string, mixed> Token tree
     */
    private function parse(string $source): array
    {
        $this->parser->setPragmas($this->getPragmas());

        return $this->parser->parse($this->tokenize($source));
    }

    /**
     * Helper method to compile a Mustache template.
     *
     * @see Compiler::compile
     *
     * @return string generated Mustache template class code
     */
    private function compile(string|Source $source): string
    {
        $name = $this->getTemplateClassName($source);

        $this->log(
            LogLevel::INFO,
            'Compiling template to "{className}" class',
            ['className' => $name],
        );

        if ($source instanceof Source) {
            $source = $source->getSource();
        }

        $tree = $this->parse($source);

        $this->compiler->setPragmas($this->getPragmas());

        return $this->compiler->compile(
            $source,
            $tree,
            $name,
            isset($this->escape),
            $this->charset,
            $this->strictCallables,
            $this->entityFlags,
        );
    }

    /**
     * Add a log record if logging is enabled.
     *
     * @param string $level   The logging level
     * @param string $message The log message
     * @param array<string, mixed> $context The log context
     */
    private function log(string $level, string $message, array $context = []): void
    {
        if (! isset($this->logger)) {
            return;
        }

        $this->logger->log($level, $message, $context);
    }
}
