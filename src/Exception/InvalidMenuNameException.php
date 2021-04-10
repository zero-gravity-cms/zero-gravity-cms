<?php

namespace ZeroGravity\Cms\Exception;

use InvalidArgumentException;
use Throwable;

class InvalidMenuNameException extends InvalidArgumentException implements ZeroGravityException
{
    public function __construct(string $menuName = '', int $code = 0, Throwable $previous = null)
    {
        parent::__construct("Menu definition \"$menuName\" does not exist", $code, $previous);
    }
}
