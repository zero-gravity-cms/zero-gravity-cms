<?php

namespace ZeroGravity\Cms\Content\Finder;

interface PageFinderFilters
{
    /**
     * @return callable[]|PageFinderFilter[]
     */
    public function getFilters(): array;
}
