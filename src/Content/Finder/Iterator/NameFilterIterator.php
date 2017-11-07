<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * NameFilterIterator filters pages by name patterns (a regexp, a glob, or a string).
 */
class NameFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->isAccepted($this->current()->getName());
    }
}
