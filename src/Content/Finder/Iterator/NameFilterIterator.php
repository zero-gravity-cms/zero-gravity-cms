<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * NameFilterIterator filters pages by name patterns (a regexp, a glob, or a string).
 */
final class NameFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->getName());
    }
}
