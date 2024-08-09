<?php

declare(strict_types=1);

class Complex
{
    public $header = 'Colors';

    public $item = [
        ['name' => 'red', 'current' => true, 'url' => '#Red'],
        ['name' => 'green', 'current' => false, 'url' => '#Green'],
        ['name' => 'blue', 'current' => false, 'url' => '#Blue'],
    ];

    public function notEmpty()
    {
        return ! $this->isEmpty();
    }

    public function isEmpty()
    {
        return count($this->item) === 0;
    }
}
