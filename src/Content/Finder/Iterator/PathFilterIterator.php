<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * PathFilterIterator filters pages by path patterns (a regexp, a glob, or a string).
 */
final class PathFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->getPath()->toString());
    }
}
