<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use Iterator;
use IteratorAggregate;
use Traversable;

/**
 * SortableIterator applies a sort on a given Iterator.
 */
final readonly class LimitAndOffsetIterator implements IteratorAggregate
{
    /**
     * @param Traversable $iterator The Iterator to filter
     */
    public function __construct(
        private Traversable $iterator,
        private ?int $limit,
        private int $offset,
    ) {
    }

    public function getIterator(): Iterator
    {
        $array = iterator_to_array($this->iterator, true);
        $slice = array_slice($array, $this->offset, $this->limit, true);

        return new ArrayIterator($slice);
    }
}
