<?php

declare(strict_types=1);

namespace Mustache\Helper;

use Mustache\Exception\UnknownHelperException;
use Mustache\HelperCollection;
use Override;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;

/**
 * A helper collection that lazily retrieves its helpers from a PSR container instance
 *
 * @psalm-no-seal-properties
 */
final readonly class ContainerBackedHelperCollection implements HelperCollection
{
    /** @param array<non-empty-string, non-empty-string> $helpers */
    public function __construct(
        private array $helpers,
        private ContainerInterface $container,
    ) {
    }

    #[Override]
    public function has(string $name): bool
    {
        $id = $this->helpers[$name] ?? null;
        if ($id === null) {
            return false;
        }

        return $this->container->has($id);
    }

    #[Override]
    public function get(string $name): mixed
    {
        $id = $this->helpers[$name] ?? null;
        if ($id === null) {
            throw new UnknownHelperException($name);
        }

        try {
            return $this->container->get($id);
        } catch (NotFoundExceptionInterface | ContainerExceptionInterface $e) {
            throw new UnknownHelperException($name, $e);
        }
    }

    #[Override]
    public function isEmpty(): bool
    {
        return $this->helpers === [];
    }

    #[Override]
    public function __isset(string $name): bool
    {
        return $this->has($name);
    }

    #[Override]
    public function __get(string $name): mixed
    {
        return $this->get($name);
    }
}
