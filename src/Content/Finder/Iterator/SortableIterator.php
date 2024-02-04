<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use Closure;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * SortableIterator applies a sort on a given Iterator.
 *
 * @implements IteratorAggregate<string, ReadablePage>
 */
final class SortableIterator implements IteratorAggregate
{
    public const SORT_BY_DATE = 'date';
    public const SORT_BY_EXTRA_VALUE = 'extra';
    public const SORT_BY_FILESYSTEM_PATH = 'filesystemPath';
    public const SORT_BY_NAME = 'name';
    public const SORT_BY_PATH = 'path';
    public const SORT_BY_PUBLISH_DATE = 'publishDate';
    public const SORT_BY_SLUG = 'slug';
    public const SORT_BY_TITLE = 'title';

    /**
     * @var callable
     */
    private $sortBy;

    /**
     * @param Iterator<string, ReadablePage>                      $iterator The Iterator to filter
     * @param self::SORT_BY_*|Closure|array{0: string, 1: string} $sortBy   the sort type (one of the SORT_BY_* constants),
     *                                                                      a PHP closure or
     *                                                                      an array holding a SORT_BY_ type and an additional parameter
     *
     * @throws InvalidArgumentException
     */
    public function __construct(
        private readonly Iterator $iterator,
        string|Closure|array $sortBy,
    ) {
        if ($sortBy instanceof Closure) {
            $this->sortBy = $sortBy;

            return;
        }
        $parameter = null;
        if (is_array($sortBy) && 2 === count($sortBy)) {
            [$sortBy, $parameter] = $sortBy;
        }
        if (is_array($sortBy)) {
            throw new InvalidArgumentException('Arrays not holding a sorting type and parameters are not supported');
        }

        Assert::oneOf($sortBy, [
            self::SORT_BY_DATE,
            self::SORT_BY_EXTRA_VALUE,
            self::SORT_BY_FILESYSTEM_PATH,
            self::SORT_BY_NAME,
            self::SORT_BY_PATH,
            self::SORT_BY_PUBLISH_DATE,
            self::SORT_BY_SLUG,
            self::SORT_BY_TITLE,
        ]);

        $this->configureSortFunction($sortBy, $parameter);
    }

    /**
     * @throws InvalidArgumentException
     */
    private function configureSortFunction(string $sortBy, string $parameter = null): void
    {
        match ($sortBy) {
            self::SORT_BY_NAME,
            self::SORT_BY_SLUG,
            self::SORT_BY_TITLE,
            self::SORT_BY_EXTRA_VALUE => $this->sortByGetterOrPath('get'.ucfirst($sortBy), $parameter),

            self::SORT_BY_DATE,
            self::SORT_BY_PUBLISH_DATE => $this->sortByDateOrPath('get'.ucfirst($sortBy)),

            self::SORT_BY_PATH,
            self::SORT_BY_FILESYSTEM_PATH => $this->sortByGetter('get'.ucfirst($sortBy)),
            default => throw new InvalidArgumentException('The SortableIterator takes a PHP callable or a valid built-in sort algorithm as an argument.'),
        };
    }

    public function getIterator(): Iterator
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sortBy);

        return new ArrayIterator($array);
    }

    private function sortByGetterOrPath(string $getter, string $parameter = null): void
    {
        $this->sortBy = static function (Page $pageA, Page $pageB) use ($getter, $parameter): int {
            $valueA = $pageA->$getter($parameter);
            $valueB = $pageB->$getter($parameter);
            if (mb_strtolower($valueA) === mb_strtolower($valueB)) {
                return strcasecmp($pageA->getPath(), $pageB->getPath());
            }

            return strcasecmp($valueA, $valueB);
        };
    }

    private function sortByDateOrPath(string $getter): void
    {
        $this->sortBy = static function (Page $pageA, Page $pageB) use ($getter): int {
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

            return (int) ($valueA->format('U') - $valueB->format('U'));
        };
    }

    private function sortByGetter(string $getter): void
    {
        $this->sortBy = static fn (Page $pageA, Page $pageB): int => strcasecmp((string) $pageA->$getter()->toString(), (string) $pageB->$getter()->toString());
    }
}
