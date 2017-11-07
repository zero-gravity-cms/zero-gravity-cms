<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * SlugFilterIterator filters pages by slug patterns (a regexp, a glob, or a string).
 */
class SlugFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->isAccepted($this->current()->getSlug());
    }
}
