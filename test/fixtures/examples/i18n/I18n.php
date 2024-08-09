<?php

declare(strict_types=1);

class I18n
{
    // Variable to be interpolated
    public $name = 'Bob';

    // Add a {{#__}} lambda for i18n
    public $__ = [self::class, '__trans'];

    // A *very* small i18n dictionary :)
    private static $dictionary = [
        'Hello.'                 => 'Hola.',
        'My name is {{ name }}.' => 'Me llamo {{ name }}.',
    ];

    public static function __trans($text)
    {
        return self::$dictionary[$text] ?? $text;
    }
}
