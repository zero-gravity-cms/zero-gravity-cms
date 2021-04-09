<?php

namespace ZeroGravity\Cms\Content\Finder;

use ZeroGravity\Cms\Exception\FilterException;

final class FilterRegistry
{
    /**
     * @var PageFinderFilter[]|callable[]
     */
    private $filters;

    public function addFilters(PageFinderFilters $filters)
    {
        foreach ($filters->getFilters() as $filterName => $filter) {
            $this->addFilter($filterName, $filter);
        }
    }

    /**
     * @param string                    $filterName
     * @param callable|PageFinderFilter $filter
     */
    public function addFilter($filterName, $filter)
    {
        if (isset($this->filters[$filterName])) {
            throw FilterException::filterAlreadyExists($filterName);
        }
        if (!$this->isValidFilter($filter)) {
            throw FilterException::notAValidFilter($filterName, $filter);
        }

        $this->filters[$filterName] = $filter;
    }

    /**
     * @param $filter
     */
    private function isValidFilter($filter): bool
    {
        return is_callable($filter) || $filter instanceof PageFinderFilter;
    }

    public function applyFilter(PageFinder $pageFinder, string $filterName, array $filterOptions): PageFinder
    {
        if (!isset($this->filters[$filterName])) {
            throw FilterException::filterDoesNotExist($filterName);
        }

        if ($this->filters[$filterName] instanceof PageFinderFilter) {
            $pageFinder = $this->filters[$filterName]->apply($pageFinder, $filterOptions);
        } elseif (is_callable($this->filters[$filterName])) {
            $pageFinder = call_user_func_array($this->filters[$filterName], [$pageFinder, $filterOptions]);
        }

        return $pageFinder;
    }
}
