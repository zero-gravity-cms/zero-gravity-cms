<?php

namespace ZeroGravity\Cms\Content\Finder\Comparator;

use InvalidArgumentException;
use Symfony\Component\Finder\Comparator\Comparator;

/**
 * StringComparator compiles string comparisons.
 */
final class StringComparator extends Comparator
{
    /**
     * @throws InvalidArgumentException If the test is not understood
     */
    public function __construct(string $target, string $operator = '==')
    {
        if (!preg_match('#^\s*(==|!=|[<>]=?)?\s*(.+?)\s*$#i', $target, $matches)) {
            throw new InvalidArgumentException(sprintf('Don\'t understand "%s" as a string test.', $target));
        }

        parent::__construct($matches[2], $matches[1] ?: $operator);
    }
}
