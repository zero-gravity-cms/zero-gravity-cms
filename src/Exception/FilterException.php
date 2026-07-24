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

    public static function filterDidNotReturnPageFinder(string $name, mixed $result): self
    {
        return new self(sprintf(
            'The page filter callable %s did not return a PageFinder, but "%s"',
            $name,
            get_debug_type($result),
        ));
    }

    public static function notAScalar(mixed $value): self
    {
        return new self(sprintf('Expected a scalar, got `%s`', get_debug_type($value)));
    }

    public static function notAString(mixed $value): self
    {
        return new self(sprintf('Expected a string, got `%s`', get_debug_type($value)));
    }
}
