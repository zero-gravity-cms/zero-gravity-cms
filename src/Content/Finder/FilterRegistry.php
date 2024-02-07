<?php

namespace ZeroGravity\Cms\Content\Finder;

use Closure;
use ZeroGravity\Cms\Exception\FilterException;

/**
 * @phpstan-import-type PageFinderFilterOptions from PageFinderFilter
 */
final class FilterRegistry
{
    /**
     * @var array<string, PageFinderFilter|callable|Closure>
     */
    private array $filters = [];

    public function addFilters(PageFinderFilters $filters): void
    {
        foreach ($filters->getFilters() as $filterName => $filter) {
            $this->addFilter($filterName, $filter);
        }
    }

    public function addFilter(string $filterName, callable|Closure|PageFinderFilter $filter): void
    {
        if (isset($this->filters[$filterName])) {
            throw FilterException::filterAlreadyExists($filterName);
        }

        $this->filters[$filterName] = $filter;
    }

    /**
     * @param PageFinderFilterOptions $filterOptions
     */
    public function applyFilter(PageFinder $pageFinder, string $filterName, array $filterOptions): PageFinder
    {
        if (!isset($this->filters[$filterName])) {
            throw FilterException::filterDoesNotExist($filterName, array_keys($this->filters));
        }

        if ($this->filters[$filterName] instanceof PageFinderFilter) {
            $pageFinder = $this->filters[$filterName]->apply($pageFinder, $filterOptions);
        } elseif (is_callable($this->filters[$filterName])) {
            $pageFinder = call_user_func_array($this->filters[$filterName], [$pageFinder, $filterOptions]);
        }

        return $pageFinder;
    }
}
