<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;

class FilterException extends RuntimeException implements ZeroGravityException
{
    public static function filterAlreadyExists(string $name): self
    {
        return new static(sprintf('Another filter called %s already exists', $name));
    }

    public static function filterDoesNotExist(string $name): self
    {
        return new static(sprintf('There is no page finder filter called %s', $name));
    }

    public static function notAValidFilter(string $name, $filter): self
    {
        $type = is_object($filter) ? get_class($filter) : gettype($filter);

        return new static(sprintf(
            'Filters must be a callable or PageFinderFilter. Detected type for filter %s is %s',
            $name,
            $type
        ));
    }
}
