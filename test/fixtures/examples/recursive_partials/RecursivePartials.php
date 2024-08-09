<?php

declare(strict_types=1);

class RecursivePartials
{
    public $name = 'George';
    public $child = [
        'name'  => 'Dan',
        'child' => [
            'name'  => 'Justin',
            'child' => false,
        ],
    ];
}
