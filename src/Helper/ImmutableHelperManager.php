<?php

declare(strict_types=1);

namespace Mustache\Helper;

use Mustache\Exception\UnknownHelperException;
use Mustache\HelperManager;
use Override;

/**
 * A helper collection that cannot be modified at runtime
 *
 * @psalm-no-seal-properties
 */
final readonly class ImmutableHelperManager implements HelperManager
{
    /** @param array<string, mixed> $helpers */
    public function __construct(private array $helpers)
    {
    }

    #[Override]
    public function has(string $name): bool
    {
        return isset($this->helpers[$name]);
    }

    #[Override]
    public function get(string $name): mixed
    {
        if (isset($this->helpers[$name])) {
            return $this->helpers[$name];
        }

        throw new UnknownHelperException($name);
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
