<?php declare(strict_types=1);

namespace SWF\Exception;

use Exception;
use Psr\SimpleCache\CacheException;
use Psr\SimpleCache\InvalidArgumentException;

class CacherException extends Exception implements CacheException, InvalidArgumentException
{
}
