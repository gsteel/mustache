<?php

declare(strict_types=1);

namespace Mustache\Loader;

use Mustache\Exception\RuntimeException;
use Mustache\Exception\UnknownTemplateException;
use Mustache\Source;
use Mustache\Source\FilesystemSource;

use function array_key_exists;
use function file_exists;

/**
 * Mustache Template production filesystem Loader implementation.
 *
 * A production-ready FilesystemLoader, which doesn't require reading a file if it already exists in the template cache.
 *
 * {@inheritDoc}
 */
class ProductionFilesystemLoader extends FilesystemLoader
{
    /** @var list<string> */
    private array $statProps;

    /**
     * Mustache production filesystem Loader constructor.
     *
     * Passing an $options array allows overriding certain Loader options during instantiation:
     *
     *     $options = array(
     *         // The filename extension used for Mustache templates. Defaults to '.mustache'
     *         'extension' => '.ms',
     *         'stat_props' => array('size', 'mtime'),
     *     );
     *
     * Specifying 'stat_props' overrides the stat properties used to invalidate the template cache. By default, this
     * uses 'mtime' and 'size', but this can be set to any of the properties supported by stat():
     *
     *     http://php.net/manual/en/function.stat.php
     *
     * You can also disable filesystem stat entirely:
     *
     *     $options = array('stat_props' => null);
     *
     * But with great power comes great responsibility. Namely, if you disable stat-based cache invalidation,
     * YOU MUST CLEAR THE TEMPLATE CACHE YOURSELF when your templates change. Make it part of your build or deploy
     * process so you don't forget!
     *
     * @param string $baseDir Base directory containing Mustache template files.
     * @param array{extension?: string, stat_props?: list<string>|null} $options
     *
     * @throws RuntimeException if $baseDir does not exist.
     */
    public function __construct(string $baseDir, array $options = [])
    {
        parent::__construct($baseDir, $options);

        if (array_key_exists('stat_props', $options)) {
            if (empty($options['stat_props'])) {
                $this->statProps = [];
            } else {
                $this->statProps = $options['stat_props'];
            }
        } else {
            $this->statProps = ['size', 'mtime'];
        }
    }

    /**
     * Helper function for loading a Mustache file by name.
     *
     * @return Source Mustache Template source
     *
     * @throws UnknownTemplateException If a template file is not found.
     */
    protected function loadFile(string $name): Source
    {
        $fileName = $this->getFileName($name);

        if (! file_exists($fileName)) {
            throw new UnknownTemplateException($name);
        }

        return new FilesystemSource($fileName, $this->statProps);
    }
}
