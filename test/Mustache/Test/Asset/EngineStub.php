<?php

declare(strict_types=1);

namespace Mustache\Test\Asset;

use Mustache\Cache;
use Mustache\Engine;
use Mustache\Template;
use Override;

final class EngineStub extends Engine
{
    public string $source;
    public Template $template;

    #[Override]
    public function loadTemplate(string $source): Template
    {
        $this->source = $source;

        return $this->template;
    }

    public function getProtectedLambdaCache(): Cache
    {
        return $this->getLambdaCache();
    }
}
