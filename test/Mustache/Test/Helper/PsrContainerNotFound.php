<?php

declare(strict_types=1);

namespace Mustache\Test\Helper;

use Exception;
use Psr\Container\NotFoundExceptionInterface;

final class PsrContainerNotFound extends Exception implements NotFoundExceptionInterface
{
}
