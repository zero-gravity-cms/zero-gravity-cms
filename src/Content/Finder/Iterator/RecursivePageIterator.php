<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use RecursiveIterator;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * @method ReadablePage current()
 *
 * @implements RecursiveIterator<string, ReadablePage>
 *
 * @extends  ArrayIterator<string, ReadablePage>
 */
final class RecursivePageIterator extends ArrayIterator implements RecursiveIterator
{
    /**
     * Returns if an iterator can be created for the current pages children.
     *
     * @return bool true if the current entry can be iterated over, otherwise returns false
     */
    public function hasChildren(): bool
    {
        return $this->current()->hasChildren();
    }

    /**
     * Returns an iterator for the current pages children.
     *
     * @return RecursiveIterator<string, ReadablePage> an iterator for the current entry
     */
    public function getChildren(): RecursiveIterator
    {
        return new self($this->current()->getChildren()->toArray());
    }
}
