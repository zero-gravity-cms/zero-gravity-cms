<?php

namespace ZeroGravity\Cms\Content\Finder;

interface PageFinderFilter
{
    public function apply(PageFinder $pageFinder, array $filterOptions): PageFinder;
}
