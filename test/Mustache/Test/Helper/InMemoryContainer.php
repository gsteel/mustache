<?php

declare(strict_types=1);

namespace Mustache\Test\Helper;

use Override;
use Psr\Container\ContainerInterface;

final readonly class InMemoryContainer implements ContainerInterface
{
    /** @param array<string, mixed> $services */
    public function __construct(private array $services = [])
    {
    }

    #[Override]
    public function get(string $id): mixed
    {
        if (! isset($this->services[$id])) {
            throw new PsrContainerNotFound();
        }

        return $this->services[$id];
    }

    #[Override]
    public function has(string $id): bool
    {
        return isset($this->services[$id]);
    }
}
