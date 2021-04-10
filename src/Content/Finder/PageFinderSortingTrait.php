<?php

namespace ZeroGravity\Cms\Content\Finder;

use Closure;
use Iterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SortableIterator;

trait PageFinderSortingTrait
{
    private $sort;

    /**
     * Sorts pages by an anonymous function.
     *
     * The anonymous function receives two Page instances to compare.
     *
     * @param Closure $closure An anonymous function
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sort(Closure $closure)
    {
        $this->sort = $closure;

        return $this;
    }

    /**
     * Sorts pages by name.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortByName()
    {
        $this->sort = SortableIterator::SORT_BY_NAME;

        return $this;
    }

    /**
     * Sorts pages by name.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortBySlug()
    {
        $this->sort = SortableIterator::SORT_BY_SLUG;

        return $this;
    }

    /**
     * Sorts pages by title.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortByTitle()
    {
        $this->sort = SortableIterator::SORT_BY_TITLE;

        return $this;
    }

    /**
     * Sorts pages by date.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortByDate()
    {
        $this->sort = SortableIterator::SORT_BY_DATE;

        return $this;
    }

    /**
     * Sorts pages by publish date.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortByPublishDate()
    {
        $this->sort = SortableIterator::SORT_BY_PUBLISH_DATE;

        return $this;
    }

    /**
     * Sorts pages by path.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortByPath()
    {
        $this->sort = SortableIterator::SORT_BY_PATH;

        return $this;
    }

    /**
     * Sorts pages by filesystem path.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortByFilesystemPath()
    {
        $this->sort = SortableIterator::SORT_BY_FILESYSTEM_PATH;

        return $this;
    }

    /**
     * Sorts pages by extra value.
     *
     * @return $this
     *
     * @see SortableIterator
     */
    public function sortByExtra($name)
    {
        $this->sort = [SortableIterator::SORT_BY_EXTRA_VALUE, $name];

        return $this;
    }

    private function applySortIterator(Iterator $iterator): Iterator
    {
        if (null !== $this->sort) {
            $iteratorAggregate = new SortableIterator($iterator, $this->sort);
            $iterator = $iteratorAggregate->getIterator();
        }

        return $iterator;
    }
}
