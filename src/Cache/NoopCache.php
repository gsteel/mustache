<?php

declare(strict_types=1);

namespace Mustache\Cache;

use Psr\Log\LogLevel;

/**
 * Mustache Cache in-memory implementation.
 *
 * The in-memory cache is used for uncached lambda section templates. It's also useful during development, but is not
 * recommended for production use.
 */
class NoopCache extends AbstractCache
{
    /**
     * Loads nothing. Move along.
     */
    public function load(string $key): bool
    {
        return false;
    }

    /**
     * Loads the compiled Mustache Template class without caching.
     */
    public function cache(string $key, string $value): void
    {
        $this->log(
            LogLevel::WARNING,
            'Template cache disabled, evaluating "{className}" class at runtime',
            ['className' => $key],
        );
        eval('?>' . $value);
    }
}
