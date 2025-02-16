<?php

declare(strict_types=1);

namespace Mustache\Exception;

use Mustache\Exception;

/** phpcs:disable SlevomatCodingStandard.Classes.RequireAbstractOrFinal */
class InvalidArgumentException extends \InvalidArgumentException implements Exception
{
}
