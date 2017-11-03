<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator;
use ZeroGravity\Cms\Content\Page;

/**
 * SlugFilterIterator filters pages by slug patterns (a regexp, a glob, or a string).
 *
 * @method Page current()
 */
class SlugFilterIterator extends MultiplePcreFilterIterator
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

    /**
     * Converts glob to regexp.
     *
     * PCRE patterns are left unchanged.
     * Glob strings are transformed with Glob::toRegex().
     *
     * @param string $str Pattern: glob or regexp
     *
     * @return string regexp corresponding to a given glob or regexp
     */
    protected function toRegex($str)
    {
        return $this->isRegex($str) ? $str : Glob::toRegex($str);
    }
}
