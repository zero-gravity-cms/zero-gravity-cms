<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * ContentTypeFilterIterator filters pages by contentType patterns (a regexp, a glob, or a string).
 */
final class ContentTypeFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        return $this->isAccepted($this->current()->getContentType());
    }
}
