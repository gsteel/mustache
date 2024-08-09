<?php

declare(strict_types=1);

namespace Mustache\Loader;

use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader;
use Mustache\Source;

use function array_key_exists;
use function file_exists;
use function file_get_contents;
use function is_dir;
use function ltrim;
use function realpath;
use function sprintf;
use function strlen;
use function strpos;
use function substr;

/**
 * Mustache Template filesystem Loader implementation.
 *
 * A FilesystemLoader instance loads Mustache Template source from the filesystem by name:
 *
 *     $loader = new Mustache\Loader\FilesystemLoader(dirname(__FILE__).'/views');
 *     $tpl = $loader->load('foo'); // equivalent to `file_get_contents(dirname(__FILE__).'/views/foo.mustache');
 *
 * This is probably the most useful Mustache Loader implementation. It can be used for partials and normal Templates:
 *
 *     $m = new Mustache(array(
 *          'loader'          => new Mustache\Loader\FilesystemLoader(dirname(__FILE__).'/views'),
 *          'partials_loader' => new Mustache\Loader\FilesystemLoader(dirname(__FILE__).'/views/partials'),
 *     ));
 */
class FilesystemLoader implements Loader
{
    private string $baseDir;
    private string $extension = '.mustache';
    /** @var array<string, string> */
    private array $templates = [];

    /**
     * Mustache filesystem Loader constructor.
     *
     * Passing an $options array allows overriding certain Loader options during instantiation:
     *
     *     $options = array(
     *         // The filename extension used for Mustache templates. Defaults to '.mustache'
     *         'extension' => '.ms',
     *     );
     *
     * @param string $baseDir Base directory containing Mustache template files
     * @param array{extension?: string} $options Array of Mustache\Loader options (default: [])
     *
     * @throws RuntimeException if $baseDir does not exist.
     */
    public function __construct(string $baseDir, array $options = [])
    {
        if ($this->shouldCheckPath($baseDir) === false) {
            $baseDir = is_dir($baseDir) ? realpath($baseDir) : $baseDir;
        }

        if ($this->shouldCheckPath($baseDir) && ! is_dir($baseDir)) {
            throw new RuntimeException(
                sprintf('Mustache\Loader\FilesystemLoader baseDir must be a directory: %s', $baseDir),
            );
        }

        $this->baseDir = $baseDir;

        if (! array_key_exists('extension', $options)) {
            return;
        }

        if (empty($options['extension'])) {
            $this->extension = '';
        } else {
            $this->extension = '.' . ltrim($options['extension'], '.');
        }
    }

    /**
     * Load a Template by name.
     *
     *     $loader = new Mustache\Loader\FilesystemLoader(dirname(__FILE__).'/views');
     *     $loader->load('admin/dashboard'); // loads "./views/admin/dashboard.mustache";
     *
     * @inheritDoc
     */
    public function load(string $name)
    {
        if (! isset($this->templates[$name])) {
            $this->templates[$name] = $this->loadFile($name);
        }

        return $this->templates[$name];
    }

    /**
     * Helper function for loading a Mustache file by name.
     *
     * @return string|Source Mustache Template source
     *
     * @throws UnknownTemplateException If a template file is not found.
     */
    protected function loadFile(string $name)
    {
        $fileName = $this->getFileName($name);

        if ($this->shouldCheckPath($fileName) && ! file_exists($fileName)) {
            throw new UnknownTemplateException($name);
        }

        return file_get_contents($fileName);
    }

    /**
     * Helper function for getting a Mustache template file name.
     *
     * @return string Template file name
     */
    protected function getFileName(string $name): string
    {
        $fileName = $this->baseDir . '/' . $name;
        if (substr($fileName, 0 - strlen($this->extension)) !== $this->extension) {
            $fileName .= $this->extension;
        }

        return $fileName;
    }

    /**
     * Only check if baseDir is a directory and requested templates are files if
     * baseDir is using the filesystem stream wrapper.
     *
     * @return bool Whether to check `is_dir` and `file_exists`
     */
    private function shouldCheckPath(string $path): bool
    {
        return strpos($path, '://') === false || strpos($path, 'file://') === 0;
    }
}
