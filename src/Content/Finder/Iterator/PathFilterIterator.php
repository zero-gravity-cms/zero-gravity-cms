<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * PathFilterIterator filters pages by path patterns (a regexp, a glob, or a string).
 */
class PathFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->isAccepted($this->current()->getPath()->toString());
    }
}
