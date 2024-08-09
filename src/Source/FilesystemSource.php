<?php

declare(strict_types=1);

namespace Mustache\Source;

use Mustache\Exception\RuntimeException;
use Mustache\Source;

use function file_get_contents;
use function json_encode;
use function sprintf;
use function stat;

/**
 * Mustache template Filesystem Source.
 *
 * This template Source uses stat() to generate the Source key, so that using
 * pre-compiled templates doesn't require hitting the disk to read the source.
 * It is more suitable for production use, and is used by default in the
 * ProductionFilesystemLoader.
 */
class FilesystemSource implements Source
{
    /** @var array<string, int>|false */
    private array|false $stat;

    /**
     * Filesystem Source constructor.
     *
     * @param list<string> $statProps
     */
    public function __construct(private string $fileName, private array $statProps)
    {
    }

    /**
     * Get the Source key (used to generate the compiled class name).
     *
     * @throws RuntimeException when a source file cannot be read.
     */
    public function getKey(): string
    {
        $chunks = [
            'fileName' => $this->fileName,
        ];

        if (! empty($this->statProps)) {
            if (! isset($this->stat)) {
                $this->stat = stat($this->fileName);
            }

            if ($this->stat === false) {
                throw new RuntimeException(sprintf('Failed to read source file "%s".', $this->fileName));
            }

            foreach ($this->statProps as $prop) {
                $chunks[$prop] = $this->stat[$prop];
            }
        }

        return json_encode($chunks);
    }

    /**
     * Get the template Source.
     */
    public function getSource(): string
    {
        return file_get_contents($this->fileName);
    }
}
