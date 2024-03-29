<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use Iterator;
use Symfony\Component\Finder\Comparator\DateComparator;
use Traversable;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * DateRangeFilterIterator filters out pages that are not in the given date range or do not have a date defined.
 *
 * @method ReadablePage current()
 *
 * @extends FilterIterator<string, ReadablePage, Traversable<string, ReadablePage>>
 */
final class DateRangeFilterIterator extends FilterIterator
{
    /**
     * @param Iterator         $iterator    The Iterator to filter
     * @param DateComparator[] $comparators An array of DateComparator instances
     */
    public function __construct(
        Iterator $iterator,
        private readonly array $comparators
    ) {
        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        $date = $this->current()->getDate();
        if (null === $date) {
            return false;
        }

        $date = $date->format('U');
        foreach ($this->comparators as $compare) {
            if (!$compare->test($date)) {
                return false;
            }
        }

        return true;
    }
}
