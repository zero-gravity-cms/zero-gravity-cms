<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * SortableIterator applies a sort on a given Iterator.
 *
 * @implements IteratorAggregate<string, ReadablePage>
 */
final readonly class LimitAndOffsetIterator implements IteratorAggregate
{
    /**
     * @param Iterator $iterator The Iterator to filter
     */
    public function __construct(
        private Iterator $iterator,
        private ?int $limit,
        private int $offset,
    ) {
    }

    /**
     * @return Iterator<string, ReadablePage>
     */
    public function getIterator(): Iterator
    {
        $array = iterator_to_array($this->iterator, true);
        $slice = array_slice($array, $this->offset, $this->limit, true);

        return new ArrayIterator($slice);
    }
}
