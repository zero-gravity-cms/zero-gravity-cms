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

    private $iterator;
    private $sort;

    /**
     * @param \Traversable    $iterator The Iterator to filter
     * @param string|callable $sort     The sort type (SORT_BY_NAME, SORT_BY_TYPE, or a PHP callback)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\Traversable $iterator, $sort)
    {
        $this->iterator = $iterator;

        switch ($sort) {
            case self::SORT_BY_NAME:
            case self::SORT_BY_SLUG:
            case self::SORT_BY_TITLE:
                $getter = 'get'.ucfirst($sort);
                $this->sort = function (Page $pageA, Page $pageB) use ($getter) {
                    $valueA = $pageA->$getter();
                    $valueB = $pageB->$getter();
                    if (mb_strtolower($valueA) === mb_strtolower($valueB)) {
                        return strcasecmp($pageA->getPath(), $pageB->getPath());
                    }

                    return strcasecmp($valueA, $valueB);
                };
                break;

            case self::SORT_BY_DATE:
            case self::SORT_BY_PUBLISH_DATE:
                $getter = 'get'.ucfirst($sort);
                $this->sort = function (Page $pageA, Page $pageB) use ($getter) {
                    $valueA = $pageA->$getter();
                    $valueB = $pageB->$getter();
                    if (null !== $valueA && null === $valueB) {
                        return 1;
                    }
                    if (null === $valueA && null !== $valueB) {
                        return -1;
                    }
                    if ((null === $valueA && null === $valueB)
                        || ($valueA->format('U') === $valueB->format('U'))
                    ) {
                        return strcasecmp($pageA->getPath(), $pageB->getPath());
                    }

                    return $valueA->format('U') - $valueB->format('U');
                };
                break;

            case self::SORT_BY_PATH:
            case self::SORT_BY_FILESYSTEM_PATH:
                $getter = 'get'.ucfirst($sort);
                $this->sort = function (Page $pageA, Page $pageB) use ($getter) {
                    return strcasecmp($pageA->$getter()->toString(), $pageB->$getter()->toString());
                };
                break;

            default:
                if (is_callable($sort)) {
                    $this->sort = $sort;
                } else {
                    throw new \InvalidArgumentException('The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.');
                }
        }
    }

    public function getIterator()
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sort);

        return new \ArrayIterator($array);
    }
}
