<?php

declare(strict_types=1);

namespace Mustache\Cache;

use Mustache\Exception\RuntimeException;
use Psr\Log\LogLevel;

use function basename;
use function chmod;
use function dirname;
use function file_put_contents;
use function is_dir;
use function is_file;
use function mkdir;
use function rename;
use function sprintf;
use function tempnam;
use function umask;

/**
 * Mustache Cache filesystem implementation.
 *
 * A FilesystemCache instance caches Mustache Template classes from the filesystem by name:
 *
 *     $cache = new \Mustache\Cache\FilesystemCache(dirname(__FILE__).'/cache');
 *     $cache->cache($className, $compiledSource);
 *
 * The FilesystemCache benefits from any opcode caching that may be setup in your environment. So do that, k?
 */
final class FilesystemCache extends AbstractCache
{
    /**
     * Filesystem cache constructor.
     *
     * @param string $baseDir  Directory for compiled templates
     * @param int|null $fileMode Override default permissions for cache files. Defaults to using the system umask
     */
    public function __construct(private string $baseDir, private int|null $fileMode = null)
    {
    }

    /**
     * Load the class from cache using `require_once`.
     */
    public function load(string $key): bool
    {
        $fileName = $this->getCacheFilename($key);
        if (! is_file($fileName)) {
            return false;
        }

        require_once $fileName;

        return true;
    }

    /**
     * Mustache\Cache and load the compiled class.
     */
    public function cache(string $key, string $value): void
    {
        $fileName = $this->getCacheFilename($key);

        $this->log(
            LogLevel::DEBUG,
            'Writing to template cache: "{fileName}"',
            ['fileName' => $fileName],
        );

        $this->writeFile($fileName, $value);
        $this->load($key);
    }

    /**
     * Build the cache filename.
     * Subclasses should override for custom cache directory structures.
     */
    protected function getCacheFilename(string $name): string
    {
        return sprintf('%s/%s.php', $this->baseDir, $name);
    }

    /**
     * Create cache directory.
     *
     * @throws RuntimeException If unable to create directory.
     */
    private function buildDirectoryForFilename(string $fileName): string
    {
        $dirName = dirname($fileName);
        if (! is_dir($dirName)) {
            $this->log(
                LogLevel::INFO,
                'Creating Mustache template cache directory: "{dirName}"',
                ['dirName' => $dirName],
            );

            @mkdir($dirName, 0777, true);
            // @codeCoverageIgnoreStart
            if (! is_dir($dirName)) {
                throw new RuntimeException(sprintf('Failed to create cache directory "%s".', $dirName));
            }
            // @codeCoverageIgnoreEnd
        }

        return $dirName;
    }

    /**
     * Write cache file.
     *
     * @throws RuntimeException If unable to write file.
     */
    private function writeFile(string $fileName, string $value): void
    {
        $dirName = $this->buildDirectoryForFilename($fileName);

        $this->log(
            LogLevel::DEBUG,
            'Caching compiled template to "{fileName}"',
            ['fileName' => $fileName],
        );

        $tempFile = tempnam($dirName, basename($fileName));
        if (@file_put_contents($tempFile, $value) !== false) {
            if (@rename($tempFile, $fileName)) {
                $mode = $this->fileMode ?? 0666 & ~umask();
                @chmod($fileName, $mode);

                return;
            }

            // @codeCoverageIgnoreStart
            $this->log(
                LogLevel::ERROR,
                'Unable to rename Mustache temp cache file: "{tempName}" -> "{fileName}"',
                ['tempName' => $tempFile, 'fileName' => $fileName],
            );
            // @codeCoverageIgnoreEnd
        }

        // @codeCoverageIgnoreStart
        throw new RuntimeException(sprintf('Failed to write cache file "%s".', $fileName));
        // @codeCoverageIgnoreEnd
    }
}
