<?php

namespace Mustache\Source;

use Mustache\Exception\RuntimeException;
use Mustache\Source;

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
    private $fileName;
    private $statProps;
    private $stat;

    /**
     * Filesystem Source constructor.
     *
     * @param string $fileName
     * @param array $statProps
     */
    public function __construct($fileName, array $statProps)
    {
        $this->fileName = $fileName;
        $this->statProps = $statProps;
    }

    /**
     * Get the Source key (used to generate the compiled class name).
     *
     * @return string
     * @throws RuntimeException when a source file cannot be read
     *
     */
    public function getKey()
    {
        $chunks = [
            'fileName' => $this->fileName,
        ];

        if (!empty($this->statProps)) {
            if (!isset($this->stat)) {
                $this->stat = @stat($this->fileName);
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
     *
     * @return string
     */
    public function getSource()
    {
        return file_get_contents($this->fileName);
    }
}
