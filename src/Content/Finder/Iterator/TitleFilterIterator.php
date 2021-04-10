<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * TitleFilterIterator filters pages by title patterns (a regexp, a glob, or a string).
 */
final class TitleFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->getTitle());
    }
}
