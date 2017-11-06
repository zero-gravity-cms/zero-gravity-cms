<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

/**
 * FilesystemPathFilterIterator filters pages by filesystem path patterns (a regexp, a glob, or a string).
 */
class FilesystemPathFilterIterator extends MultipleGlobFilterIterator
{
    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->isAccepted($this->current()->getFilesystemPath()->toString());
    }
}
