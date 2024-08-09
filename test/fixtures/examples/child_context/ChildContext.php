<?php

declare(strict_types=1);

class ChildContext
{
    public $parent = [
        'child' => 'child works',
    ];

    public $grandparent = [
        'parent' => [
            'child' => 'grandchild works',
        ],
    ];
}
