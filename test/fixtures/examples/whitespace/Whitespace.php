<?php

declare(strict_types=1);

/**
 * Whitespace test for tag names.
 *
 * Per http://github.com/janl/mustache.js/issues/issue/34/#comment_244396
 * tags should strip leading and trailing whitespace in key names.
 *
 * `{{> tag }}` and `{{> tag}}` and `{{>tag}}` should all be equivalent.
 */
class Whitespace
{
    public $foo = 'alpha';

    public $bar = 'beta';

    public function baz()
    {
        return 'gamma';
    }

    public function qux()
    {
        return [
            ['key with space' => 'A'],
            ['key with space' => 'B'],
            ['key with space' => 'C'],
            ['key with space' => 'D'],
            ['key with space' => 'E'],
            ['key with space' => 'F'],
            ['key with space' => 'G'],
        ];
    }
}
