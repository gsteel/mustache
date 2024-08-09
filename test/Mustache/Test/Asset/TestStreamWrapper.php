<?php

declare(strict_types=1);

namespace Mustache\Test\Asset;

use function fclose;
use function feof;
use function fgets;
use function fopen;
use function preg_replace;

final class TestStreamWrapper
{
    private $filehandle;

    /**
     * Always returns false.
     *
     * @param string $path
     * @param int    $flags
     *
     * @return array
     */
    public function url_stat($path, $flags)
    {
        return false;
    }

    /**
     * Open the file.
     *
     * @param string $path
     * @param string $mode
     *
     * @return bool
     */
    public function stream_open($path, $mode)
    {
        $path = preg_replace('-^test://-', '', $path);
        $this->filehandle = fopen($path, $mode);

        return $this->filehandle !== false;
    }

    /**
     * @return array
     */
    public function stream_stat()
    {
        return array();
    }

    /**
     * @param int $count
     *
     * @return string
     */
    public function stream_read($count)
    {
        return fgets($this->filehandle, $count);
    }

    /**
     * @return bool
     */
    public function stream_eof()
    {
        return feof($this->filehandle);
    }

    /**
     * @return bool
     */
    public function stream_close()
    {
        return fclose($this->filehandle);
    }
}
