<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;

class FilterException extends RuntimeException implements ZeroGravityException
{
    public static function filterAlreadyExists(string $name): self
    {
        return new self(sprintf('Another filter called %s already exists', $name));
    }

    /**
     * @param list<string> $existingFilters
     */
    public static function filterDoesNotExist(string $name, array $existingFilters): self
    {
        return new self(sprintf(
            'There is no page finder filter called %s. Available filter names are: %s',
            $name,
            implode(', ', $existingFilters),
        ));
    }
}
