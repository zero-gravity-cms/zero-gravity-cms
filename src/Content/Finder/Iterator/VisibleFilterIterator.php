<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use Iterator;
use ZeroGravity\Cms\Content\Page;

/**
 * VisibleFilterIterator filters out pages that do not match the required visible state.
 *
 * @method Page current()
 */
final class VisibleFilterIterator extends FilterIterator
{
    /**
     * @param Iterator $iterator The Iterator to filter
     */
    public function __construct(
        Iterator $iterator,
        private readonly bool $visible,
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
        return $this->current()->isVisible() === $this->visible;
    }
}
