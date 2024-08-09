<?php

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
