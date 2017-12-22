<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ZeroGravity\Cms\Content\Page;

/**
 * SortableIterator applies a sort on a given Iterator.
 */
class SortableIterator implements \IteratorAggregate
{
    const SORT_BY_NAME = 'name';
    const SORT_BY_SLUG = 'slug';
    const SORT_BY_TITLE = 'title';
    const SORT_BY_DATE = 'date';
    const SORT_BY_PUBLISH_DATE = 'publishDate';
    const SORT_BY_PATH = 'path';
    const SORT_BY_FILESYSTEM_PATH = 'filesystemPath';
    const SORT_BY_EXTRA_VALUE = 'extra';

    private $iterator;
    private $sort;

    /**
     * @param \Traversable    $iterator The Iterator to filter
     * @param string|\Closure $sort     The sort type (SORT_BY_NAME, SORT_BY_TYPE, or a PHP closure)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\Traversable $iterator, $sort)
    {
        $this->iterator = $iterator;
        if ($sort instanceof \Closure) {
            $this->sort = $sort;

            return;
        }
        $parameter = null;
        if (is_array($sort) && 2 == count($sort)) {
            list($sort, $parameter) = $sort;
        }

        $this->configureSortFunction($sort, $parameter);
    }

    /**
     * @param $sort
     * @param $parameter
     *
     * @throws \InvalidArgumentException
     */
    private function configureSortFunction(string $sort, string $parameter = null): void
    {
        switch ($sort) {
            case self::SORT_BY_NAME:
            case self::SORT_BY_SLUG:
            case self::SORT_BY_TITLE:
            case self::SORT_BY_EXTRA_VALUE:
                $this->sortByGetterOrPath('get'.ucfirst($sort), $parameter);
                break;

            case self::SORT_BY_DATE:
            case self::SORT_BY_PUBLISH_DATE:
                $this->sortByDateOrPath('get'.ucfirst($sort));
                break;

            case self::SORT_BY_PATH:
            case self::SORT_BY_FILESYSTEM_PATH:
                $this->sortByGetter('get'.ucfirst($sort));
                break;

            default:
                throw new \InvalidArgumentException('The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.');
        }
    }

    public function getIterator()
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sort);

        return new \ArrayIterator($array);
    }

    /**
     * @param string $getter
     * @param mixed  $parameter
     */
    private function sortByGetterOrPath(string $getter, $parameter = null): void
    {
        $this->sort = function (Page $pageA, Page $pageB) use ($getter, $parameter) {
            $valueA = $pageA->$getter($parameter);
            $valueB = $pageB->$getter($parameter);
            if (mb_strtolower($valueA) === mb_strtolower($valueB)) {
                return strcasecmp($pageA->getPath(), $pageB->getPath());
            }

            return strcasecmp($valueA, $valueB);
        };
    }

    /**
     * @param $getter
     */
    private function sortByDateOrPath($getter): void
    {
        $this->sort = function (Page $pageA, Page $pageB) use ($getter) {
            $valueA = $pageA->$getter();
            $valueB = $pageB->$getter();
            if (null !== $valueA && null === $valueB) {
                return 1;
            }
            if (null === $valueA && null !== $valueB) {
                return -1;
            }
            if ($valueA == $valueB) {
                return strcasecmp($pageA->getPath(), $pageB->getPath());
            }

            return $valueA->format('U') - $valueB->format('U');
        };
    }

    /**
     * @param $getter
     */
    private function sortByGetter($getter): void
    {
        $this->sort = function (Page $pageA, Page $pageB) use ($getter) {
            return strcasecmp($pageA->$getter()->toString(), $pageB->$getter()->toString());
        };
    }
}
