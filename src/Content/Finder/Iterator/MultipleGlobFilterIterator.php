<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use Symfony\Component\Finder\Glob;
use Symfony\Component\Finder\Iterator\MultiplePcreFilterIterator;
use ZeroGravity\Cms\Content\Page;

/**
 * MultipleGlobFilterIterator is a regex filter iterator that allows glob expressions.
 *
 * @method Page current()
 */
abstract class MultipleGlobFilterIterator extends MultiplePcreFilterIterator
{
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
