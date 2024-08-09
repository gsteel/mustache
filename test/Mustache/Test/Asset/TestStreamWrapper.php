<?php

declare(strict_types=1);

namespace Mustache\Test\Asset;

use function assert;
use function fclose;
use function feof;
use function fgets;
use function fopen;
use function preg_replace;

/**
 * phpcs:ignoreFile
 * @psalm-suppress PossiblyUnusedProperty
 */
final class TestStreamWrapper
{
    /** @var resource|false */
    private $filehandle = false;
    /**
     * @var resource|null
     */
    public $context = null;

    /**
     * Always returns false.
     */
    public function url_stat(): bool
    {
        return false;
    }

    /**
     * Open the file.
     */
    public function stream_open(string $path, string $mode): bool
    {
        $path = preg_replace('-^test://-', '', $path);
        $this->filehandle = fopen($path, $mode);

        return $this->filehandle !== false;
    }

    public function stream_stat(): array
    {
        return [];
    }

    /** @return string|false */
    public function stream_read(int $count): string|bool
    {
        assert($this->filehandle !== false);

        return fgets($this->filehandle, $count);
    }

    public function stream_eof(): bool
    {
        assert($this->filehandle !== false);

        return feof($this->filehandle);
    }

    public function stream_close(): bool
    {
        assert($this->filehandle !== false);

        fclose($this->filehandle);
        $this->filehandle = false;

        return true;
    }
}
