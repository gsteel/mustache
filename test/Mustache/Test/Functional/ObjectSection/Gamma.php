<?php

declare(strict_types=1);

namespace Mustache\Test\Functional\ObjectSection;

final class Gamma
{
    public Beta $bar;

    public function __construct()
    {
        $this->bar = new Beta();
    }
}
