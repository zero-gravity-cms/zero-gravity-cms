<?php

namespace ZeroGravity\Cms\Content\Finder;

interface PageFinderFilter
{
    /**
     * @param PageFinder $pageFinder
     * @param array      $filterOptions
     *
     * @return PageFinder
     */
    public function apply(PageFinder $pageFinder, array $filterOptions): PageFinder;
}
