<?php

namespace ZeroGravity\Cms\Exception;

use RuntimeException;

class FilterException extends RuntimeException implements ZeroGravityException
{
    /**
     * @param string $name
     *
     * @return static
     */
    public static function filterAlreadyExists($name)
    {
        return new static(sprintf('Another filter called %s already exists', $name));
    }

    /**
     * @param string $name
     *
     * @return static
     */
    public static function filterDoesNotExist($name)
    {
        return new static(sprintf('There is no page finder filter called %s', $name));
    }

    /**
     * @param string $name
     * @param mixed  $filter
     *
     * @return static
     */
    public static function notAValidFilter($name, $filter)
    {
        $type = is_object($filter) ? get_class($filter) : gettype($filter);

        return new static(sprintf(
            'Filters must be a callable or PageFinderFilter. Detected type for filter %s is %s',
            $name,
            $type
        ));
    }
}
