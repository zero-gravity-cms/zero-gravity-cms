<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;

class FilterException extends RuntimeException implements ZeroGravityException
{
    public static function filterAlreadyExists(string $name): self
    {
        return new self(sprintf('Another filter called %s already exists', $name));
    }

    public static function filterDoesNotExist(string $name): self
    {
        return new self(sprintf('There is no page finder filter called %s', $name));
    }
}
