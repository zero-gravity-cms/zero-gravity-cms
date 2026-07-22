<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Finder;

use Closure;

interface PageFinderFilters
{
    /**
     * @return array<string, callable|Closure|PageFinderFilter>
     */
    public function getFilters(): array;
}
