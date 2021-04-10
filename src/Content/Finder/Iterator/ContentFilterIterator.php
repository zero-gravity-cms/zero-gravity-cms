<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * ContentFilterIterator filters pages by content patterns (a regexp, a glob, or a string).
 */
final class ContentFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        return $this->isAccepted((string) $this->current()->getContent());
    }
}
