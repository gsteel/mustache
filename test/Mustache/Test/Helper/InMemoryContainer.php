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

    /**
     * PSR Container 1.0 is supported, so no parameter type declarations
     *
     * @param string $id
     */
    #[Override]
    public function get($id): mixed // phpcs:ignore
    {
        if (! isset($this->services[$id])) {
            throw new PsrContainerNotFound();
        }

        return $this->services[$id];
    }

    /**
     * PSR Container 1.0 is supported, so no parameter type declarations
     *
     * @param string $id
     */
    #[Override]
    public function has($id): bool // phpcs:ignore
    {
        return isset($this->services[$id]);
    }
}
