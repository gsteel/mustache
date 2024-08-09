<?php

declare(strict_types=1);

namespace Mustache\Test\Functional\ObjectSection;

use stdClass;

use function array_key_exists;

final class Beta
{
    /** @var array<string, mixed> */
    private array $data = [];

    public function __construct()
    {
        $this->data['foo'] = new stdClass();
        $this->data['foo']->name = 'Foo';
        $this->data['foo']->number = 1;
    }

    public function __isset(string $name): bool
    {
        return array_key_exists($name, $this->data);
    }

    public function __get(string $name): mixed
    {
        return $this->data[$name];
    }
}
