<?php

declare(strict_types=1);

namespace Mustache\Exception;

use Mustache\Exception;

/** phpcs:disable SlevomatCodingStandard.Classes.RequireAbstractOrFinal */
class LogicException extends \LogicException implements Exception
{
}
