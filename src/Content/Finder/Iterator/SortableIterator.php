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
     * @param \Traversable $iterator The Iterator to filter
     * @param int|callable $sort     The sort type (SORT_BY_NAME, SORT_BY_TYPE, or a PHP callback)
     *
     * @throws \InvalidArgumentException
     */
    public function __construct(\Traversable $iterator, $sort)
    {
        $this->iterator = $iterator;

        if (self::SORT_BY_NAME === $sort) {
            $this->sort = function (Page $a, Page $b) {
                if (mb_strtolower($a->getName()) === mb_strtolower($b->getName())) {
                    return strcasecmp($a->getPath(), $b->getPath());
                }

                return strcasecmp($a->getName(), $b->getName());
            };
        } elseif (self::SORT_BY_SLUG === $sort) {
            $this->sort = function (Page $a, Page $b) {
                if (mb_strtolower($a->getSlug()) === mb_strtolower($b->getSlug())) {
                    return strcasecmp($a->getPath(), $b->getPath());
                }

                return strcasecmp($a->getSlug(), $b->getSlug());
            };
        } elseif (self::SORT_BY_TITLE === $sort) {
            $this->sort = function (Page $a, Page $b) {
                if (mb_strtolower($a->getTitle()) === mb_strtolower($b->getTitle())) {
                    return strcasecmp($a->getPath(), $b->getPath());
                }

                return strcasecmp($a->getTitle(), $b->getTitle());
            };
        } elseif (self::SORT_BY_DATE === $sort) {
            $this->sort = function (Page $a, Page $b) {
                if (null !== $a->getDate() && null === $b->getDate()) {
                    return 1;
                }
                if (null === $a->getDate() && null !== $b->getDate()) {
                    return -1;
                }
                if (null === $a->getDate() && null === $b->getDate()) {
                    return strcasecmp($a->getPath(), $b->getPath());
                }
                if ($a->getDate()->format('U') === $b->getDate()->format('U')) {
                    return strcasecmp($a->getPath(), $b->getPath());
                }

                return $a->getDate()->format('U') - $b->getDate()->format('U');
            };
        } elseif (self::SORT_BY_PUBLISH_DATE === $sort) {
            $this->sort = function (Page $a, Page $b) {
                if (null !== $a->getPublishDate() && null === $b->getPublishDate()) {
                    return 1;
                }
                if (null === $a->getPublishDate() && null !== $b->getPublishDate()) {
                    return -1;
                }
                if (null === $a->getPublishDate() && null === $b->getPublishDate()) {
                    return strcasecmp($a->getPath(), $b->getPath());
                }
                if ($a->getPublishDate()->format('U') === $b->getPublishDate()->format('U')) {
                    return strcasecmp($a->getPath(), $b->getPath());
                }

                return $a->getPublishDate()->format('U') - $b->getPublishDate()->format('U');
            };
        } elseif (self::SORT_BY_PATH === $sort) {
            $this->sort = function (Page $a, Page $b) {
                return strcasecmp($a->getPath(), $b->getPath());
            };
        } elseif (self::SORT_BY_FILESYSTEM_PATH === $sort) {
            $this->sort = function (Page $a, Page $b) {
                return strcasecmp($a->getFilesystemPath(), $b->getFilesystemPath());
            };
        } elseif (is_callable($sort)) {
            $this->sort = $sort;
        } else {
            throw new \InvalidArgumentException('The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.');
        }
    }

    public function getIterator()
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sort);

        return new \ArrayIterator($array);
    }
}
