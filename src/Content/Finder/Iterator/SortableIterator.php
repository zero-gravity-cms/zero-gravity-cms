<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use Closure;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Traversable;
use ZeroGravity\Cms\Content\Page;

/**
 * SortableIterator applies a sort on a given Iterator.
 */
final class SortableIterator implements IteratorAggregate
{
    public const SORT_BY_NAME = 'name';
    public const SORT_BY_SLUG = 'slug';
    public const SORT_BY_TITLE = 'title';
    public const SORT_BY_DATE = 'date';
    public const SORT_BY_PUBLISH_DATE = 'publishDate';
    public const SORT_BY_PATH = 'path';
    public const SORT_BY_FILESYSTEM_PATH = 'filesystemPath';
    public const SORT_BY_EXTRA_VALUE = 'extra';

    private Traversable $iterator;
    /**
     * @var callable
     */
    private $sortBy;

    /**
     * @param Traversable             $iterator The Iterator to filter
     * @param string|Closure|callable $sortBy   The sort type (on of the SORT_BY_* constants, or a PHP closure)
     *
     * @throws InvalidArgumentException
     */
    public function __construct(Traversable $iterator, $sortBy)
    {
        $this->iterator = $iterator;
        if ($sortBy instanceof Closure) {
            $this->sortBy = $sortBy;

            return;
        }
        $parameter = null;
        if (is_array($sortBy) && 2 === count($sortBy)) {
            [$sortBy, $parameter] = $sortBy;
        }

        $this->configureSortFunction((string) $sortBy, (string) $parameter);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function configureSortFunction(string $sortBy, string $parameter = null): void
    {
        switch ($sortBy) {
            case self::SORT_BY_NAME:
            case self::SORT_BY_SLUG:
            case self::SORT_BY_TITLE:
            case self::SORT_BY_EXTRA_VALUE:
                $this->sortByGetterOrPath('get'.ucfirst($sortBy), $parameter);
                break;

            case self::SORT_BY_DATE:
            case self::SORT_BY_PUBLISH_DATE:
                $this->sortByDateOrPath('get'.ucfirst($sortBy));
                break;

            case self::SORT_BY_PATH:
            case self::SORT_BY_FILESYSTEM_PATH:
                $this->sortByGetter('get'.ucfirst($sortBy));
                break;

            default:
                throw new InvalidArgumentException('The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.');
        }
    }

    public function getIterator(): Iterator
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sortBy);

        return new ArrayIterator($array);
    }

    private function sortByGetterOrPath(string $getter, $parameter = null): void
    {
        $this->sortBy = static function (Page $pageA, Page $pageB) use ($getter, $parameter) {
            $valueA = $pageA->$getter($parameter);
            $valueB = $pageB->$getter($parameter);
            if (mb_strtolower($valueA) === mb_strtolower($valueB)) {
                return strcasecmp($pageA->getPath(), $pageB->getPath());
            }

            return strcasecmp($valueA, $valueB);
        };
    }

    private function sortByDateOrPath($getter): void
    {
        $this->sortBy = static function (Page $pageA, Page $pageB) use ($getter) {
            $valueA = $pageA->$getter();
            $valueB = $pageB->$getter();
            if (null !== $valueA && null === $valueB) {
                return 1;
            }
            if (null === $valueA && null !== $valueB) {
                return -1;
            }
            /* @noinspection TypeUnsafeComparisonInspection */
            if ($valueA == $valueB) {
                return strcasecmp($pageA->getPath(), $pageB->getPath());
            }

            return $valueA->format('U') - $valueB->format('U');
        };
    }

    private function sortByGetter($getter): void
    {
        $this->sortBy = static fn (Page $pageA, Page $pageB) => strcasecmp($pageA->$getter()->toString(), $pageB->$getter()->toString());
    }
}
