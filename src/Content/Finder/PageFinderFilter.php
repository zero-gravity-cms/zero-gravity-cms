<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder;

/**
 * @phpstan-type PageFinderFilterOptions array<string, mixed>
 */
interface PageFinderFilter
{
    /**
     * @param PageFinderFilterOptions $filterOptions
     */
    public function apply(PageFinder $pageFinder, array $filterOptions): PageFinder;
}
