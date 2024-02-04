<?php

namespace ZeroGravity\Cms\Content\Finder;

use Closure;

interface PageFinderFilters
{
    /**
     * @return array<string, callable|Closure|PageFinderFilter>
     */
    public function getFilters(): array;
}
