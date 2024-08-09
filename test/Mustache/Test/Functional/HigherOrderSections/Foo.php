<?php

declare(strict_types=1);

namespace Mustache\Test\Functional\HigherOrderSections;

use function sprintf;
use function trim;

final class Foo
{
    public string $name = 'Justin';
    public string $lorem = 'Lorem ipsum dolor sit amet,';
    /** @var mixed */
    public $doublewrap;
    /** @var mixed */
    public $trimmer;
    /** @var mixed */
    public $wrap;
    /** @var mixed */
    public $wrapper;

    public function __construct()
    {
        $this->wrap = static function (string $text): string {
            return sprintf('<em>%s</em>', $text);
        };
    }

    public function wrapWithEm(string $text): string
    {
        return sprintf('<em>%s</em>', $text);
    }

    public function wrapWithStrong(string $text): string
    {
        return sprintf('<strong>%s</strong>', $text);
    }

    public function wrapWithBoth(string $text): string
    {
        return self::wrapWithStrong(self::wrapWithEm($text));
    }

    public static function staticTrim(string $text): string
    {
        return trim($text);
    }
}
