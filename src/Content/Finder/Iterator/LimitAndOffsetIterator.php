<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use IteratorAggregate;
use Traversable;

/**
 * SortableIterator applies a sort on a given Iterator.
 */
class LimitAndOffsetIterator implements IteratorAggregate
{
    private Traversable $iterator;

    private ?int $limit = null;

    private int $offset;

    /**
     * @param Traversable $iterator The Iterator to filter
     * @param int|null    $limit
     * @param int|null    $offset
     */
    public function __construct(Traversable $iterator, $limit, $offset)
    {
        $this->iterator = $iterator;
        $this->limit = $limit;
        $this->offset = (int) $offset;
    }

    public function getIterator()
    {
        $array = iterator_to_array($this->iterator, true);
        $slice = array_slice($array, $this->offset, $this->limit, true);

        return new ArrayIterator($slice);
    }
}
