<?php

namespace ZeroGravity\Cms\Content\Finder;

use Closure;
use Iterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SortableIterator;

trait PageFinderSortingTrait
{
    /**
     * @var string|Closure
     */
    private $sortBy;

    /**
     * Sorts pages by an anonymous function. The function will receive two Page instances to compare.
     *
     * @see SortableIterator
     */
    public function sort(Closure $closure): self
    {
        $this->sortBy = $closure;

        return $this;
    }

    /**
     * Sorts pages by name.
     *
     * @see SortableIterator
     */
    public function sortByName(): self
    {
        $this->sortBy = SortableIterator::SORT_BY_NAME;

        return $this;
    }

    /**
     * Sorts pages by slug.
     *
     * @see SortableIterator
     */
    public function sortBySlug(): self
    {
        $this->sortBy = SortableIterator::SORT_BY_SLUG;

        return $this;
    }

    /**
     * Sorts pages by title.
     *
     * @see SortableIterator
     */
    public function sortByTitle(): self
    {
        $this->sortBy = SortableIterator::SORT_BY_TITLE;

        return $this;
    }

    /**
     * Sorts pages by date.
     *
     * @see SortableIterator
     */
    public function sortByDate(): self
    {
        $this->sortBy = SortableIterator::SORT_BY_DATE;

        return $this;
    }

    /**
     * Sorts pages by publish date.
     *
     * @see SortableIterator
     */
    public function sortByPublishDate(): self
    {
        $this->sortBy = SortableIterator::SORT_BY_PUBLISH_DATE;

        return $this;
    }

    /**
     * Sorts pages by path.
     *
     * @see SortableIterator
     */
    public function sortByPath(): self
    {
        $this->sortBy = SortableIterator::SORT_BY_PATH;

        return $this;
    }

    /**
     * Sorts pages by filesystem path.
     *
     * @see SortableIterator
     */
    public function sortByFilesystemPath(): self
    {
        $this->sortBy = SortableIterator::SORT_BY_FILESYSTEM_PATH;

        return $this;
    }

    /**
     * Sorts pages by extra value.
     *
     * @see SortableIterator
     */
    public function sortByExtra(string $name): self
    {
        $this->sortBy = [SortableIterator::SORT_BY_EXTRA_VALUE, $name];

        return $this;
    }

    private function applySortIterator(Iterator $iterator): Iterator
    {
        if (null === $this->sortBy) {
            return $iterator;
        }
        $iteratorAggregate = new SortableIterator($iterator, $this->sortBy);

        return $iteratorAggregate->getIterator();
    }
}
