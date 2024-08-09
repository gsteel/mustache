<?php

namespace Mustache\Loader;

use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Loader;

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
    private $baseDir;
    private $extension = '.mustache';
    private $templates = [];

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
     * @param array $options  Array of Mustache\Loader options (default: array())
     * @throws RuntimeException if $baseDir does not exist
     *
     */
    public function __construct($baseDir, array $options = [])
    {
        $this->baseDir = $baseDir;

        if (strpos($this->baseDir, '://') === false) {
            $this->baseDir = realpath($this->baseDir);
        }

        if ($this->shouldCheckPath() && !is_dir($this->baseDir)) {
            throw new RuntimeException(sprintf('Mustache\Loader\FilesystemLoader baseDir must be a directory: %s', $baseDir));
        }

        if (array_key_exists('extension', $options)) {
            if (empty($options['extension'])) {
                $this->extension = '';
            } else {
                $this->extension = '.' . ltrim($options['extension'], '.');
            }
        }
    }

    /**
     * Load a Template by name.
     *
     *     $loader = new Mustache\Loader\FilesystemLoader(dirname(__FILE__).'/views');
     *     $loader->load('admin/dashboard'); // loads "./views/admin/dashboard.mustache";
     *
     * @param string $name
     *
     * @return string Mustache Template source
     */
    public function load($name)
    {
        if (!isset($this->templates[$name])) {
            $this->templates[$name] = $this->loadFile($name);
        }

        return $this->templates[$name];
    }

    /**
     * Helper function for loading a Mustache file by name.
     *
     * @param string $name
     *
     * @return string Mustache Template source
     * @throws UnknownTemplateException If a template file is not found
     *
     */
    protected function loadFile($name)
    {
        $fileName = $this->getFileName($name);

        if ($this->shouldCheckPath() && !file_exists($fileName)) {
            throw new UnknownTemplateException($name);
        }

        return file_get_contents($fileName);
    }

    /**
     * Helper function for getting a Mustache template file name.
     *
     * @param string $name
     *
     * @return string Template file name
     */
    protected function getFileName($name)
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
    protected function shouldCheckPath()
    {
        return strpos($this->baseDir, '://') === false || strpos($this->baseDir, 'file://') === 0;
    }
}
