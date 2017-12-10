<?php

namespace ZeroGravity\Cms\Content\Finder\Comparator;

use Symfony\Component\Finder\Comparator\Comparator;

/**
 * StringComparator compiles string comparisons
 */
class StringComparator extends Comparator
{
    /**
     * @param string|int $test A comparison string or an integer
     *
     * @throws \InvalidArgumentException If the test is not understood
     */
    public function __construct($test)
    {
        if (!preg_match('#^\s*(==|!=|[<>]=?)?\s*(.+?)\s*$#i', $test, $matches)) {
            throw new \InvalidArgumentException(sprintf('Don\'t understand "%s" as a string test.', $test));
        }

        $target = $matches[2];
        $this->setTarget($target);
        $this->setOperator(isset($matches[1]) ? $matches[1] : '==');
    }
}
