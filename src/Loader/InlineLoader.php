<?php

declare(strict_types=1);

namespace Mustache\Loader;

use Mustache\Exception\InvalidArgumentException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader;
use Mustache\Source;

use function array_key_exists;
use function explode;
use function file_get_contents;
use function is_file;
use function preg_split;
use function trim;

/**
 * A Mustache Template loader for inline templates.
 *
 * With the InlineLoader, templates can be defined at the end of any PHP source
 * file:
 *
 *     $loader  = new Mustache_Loader_InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
 *     $hello   = $loader->load('hello');
 *     $goodbye = $loader->load('goodbye');
 *
 *     __halt_compiler();
 *
 *     @@ hello
 *     Hello, {{ planet }}!
 *
 *     @@ goodbye
 *     Goodbye, cruel {{ planet }}
 *
 * Templates are deliniated by lines containing only `@@ name`.
 *
 * The Mustache\Loader\InlineLoader is well-suited to micro-frameworks such as Silex:
 *
 *     $app->register(new MustacheServiceProvider, array(
 *         'mustache.loader' => new Mustache_Loader_InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__)
 *     ));
 *
 *     $app->get('/{name}', function ($name) use ($app) {
 *         return $app['mustache']->render('hello', compact('name'));
 *     })
 *     ->value('name', 'world');
 *
 *     // ...
 *
 *     __halt_compiler();
 *
 *     @@ hello
 *     Hello, {{ name }}!
 */
class InlineLoader implements Loader
{
    /** @var array<string, string>|null */
    private array|null $templates;

    /**
     * The InlineLoader requires a filename and offset to process templates.
     *
     * The magic constants `__FILE__` and `__COMPILER_HALT_OFFSET__` are usually
     * perfectly suited to the job:
     *
     *     $loader = new Mustache_Loader_InlineLoader(__FILE__, __COMPILER_HALT_OFFSET__);
     *
     * Note that this only works if the loader is instantiated inside the same
     * file as the inline templates. If the templates are located in another
     * file, it would be necessary to manually specify the filename and offset.
     *
     * @param string $fileName The file to parse for inline templates
     * @param int    $offset   A string offset for the start of the templates.
     *                         This usually coincides with the `__halt_compiler`
     *                         call, and the `__COMPILER_HALT_OFFSET__`
     */
    public function __construct(private string $fileName, private int $offset)
    {
        if (! is_file($fileName)) {
            throw new InvalidArgumentException('Mustache\Loader\InlineLoader expects a valid filename.');
        }

        if ($offset < 0) {
            throw new InvalidArgumentException('Mustache\Loader\InlineLoader expects a valid file offset.');
        }

        $this->templates = null;
    }

    public function load(string $name): string|Source
    {
        $this->loadTemplates();

        if (! array_key_exists($name, $this->templates)) {
            throw new UnknownTemplateException($name);
        }

        return $this->templates[$name];
    }

    /**
     * Parse and load templates from the end of a source file.
     */
    protected function loadTemplates(): void
    {
        if ($this->templates !== null) {
            return;
        }

        $this->templates = [];
        $data = file_get_contents($this->fileName, false, null, $this->offset);
        foreach (preg_split('/^@@(?= [\w\d\.]+$)/m', $data, -1) as $chunk) {
            if (! trim($chunk)) {
                continue;
            }

            [$name, $content] = explode("\n", $chunk, 2);
            $this->templates[trim($name)] = trim($content);
        }
    }
}
