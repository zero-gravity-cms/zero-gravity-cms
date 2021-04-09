<?php

namespace ZeroGravity\Cms\Exception;

use InvalidArgumentException;
use Throwable;

class InvalidMenuNameException extends InvalidArgumentException implements ZeroGravityException
{
    /**
     * @param string $menuName
     * @param int    $code
     */
    public function __construct($menuName = '', $code = 0, Throwable $previous = null)
    {
        parent::__construct("Menu definition \"$menuName\" does not exist", $code, $previous);
    }
}
