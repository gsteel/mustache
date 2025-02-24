<?php

declare(strict_types=1);

namespace Mustache\Test;

use Mustache\Context;
use Mustache\Engine;
use Mustache\Template;
use Override;

use function assert;

final class TemplateStub extends Template
{
    public string|null $rendered = null;

    public function getMustache(): Engine
    {
        return $this->mustache;
    }

    #[Override]
    public function renderInternal(Context $context, string $indent = ''): string
    {
        assert($this->rendered !== null);

        return $this->rendered;
    }
}
