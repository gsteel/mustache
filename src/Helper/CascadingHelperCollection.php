<?php

declare(strict_types=1);

namespace Mustache\Helper;

use Mustache\Exception\UnknownHelperException;
use Mustache\HelperCollection;
use Override;

/**
 * A helper collection that composes multiple helper collections in a FIFO list
 *
 * @psalm-no-seal-properties
 */
final readonly class CascadingHelperCollection implements HelperCollection
{
    /** @param list<HelperCollection> $managers */
    public function __construct(private array $managers)
    {
    }

    #[Override]
    public function has(string $name): bool
    {
        foreach ($this->managers as $manager) {
            if ($manager->has($name)) {
                return true;
            }
        }

        return false;
    }

    #[Override]
    public function get(string $name): mixed
    {
        foreach ($this->managers as $manager) {
            if (! $manager->has($name)) {
                continue;
            }

            return $manager->get($name);
        }

        throw new UnknownHelperException($name);
    }

    #[Override]
    public function isEmpty(): bool
    {
        foreach ($this->managers as $manager) {
            if (! $manager->isEmpty()) {
                return false;
            }
        }

        return true;
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
