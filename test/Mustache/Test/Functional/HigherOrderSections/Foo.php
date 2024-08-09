<?php

declare(strict_types=1);

namespace Mustache\Test\Functional\HigherOrderSections;

use function sprintf;
use function trim;

final class Foo
{
    public string $name = 'Justin';
    public string $lorem = 'Lorem ipsum dolor sit amet,';
    public mixed $doublewrap;
    public mixed $trimmer;
    public mixed $wrap;
    public mixed $wrapper;

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
