<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use RecursiveIterator;
use ZeroGravity\Cms\Content\Page;

/**
 * @method Page current()
 */
class RecursivePageIterator extends \ArrayIterator implements \RecursiveIterator
{
    /**
     * Returns if an iterator can be created for the current pages children.
     *
     * @return bool true if the current entry can be iterated over, otherwise returns false
     */
    public function hasChildren()
    {
        return $this->current()->hasChildren();
    }

    /**
     * Returns an iterator for the current pages children.
     *
     * @return RecursiveIterator an iterator for the current entry
     */
    public function getChildren()
    {
        return new static($this->current()->getChildren()->toArray());
    }
}
