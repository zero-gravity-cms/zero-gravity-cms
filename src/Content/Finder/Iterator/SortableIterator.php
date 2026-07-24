<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ArrayIterator;
use Closure;
use DateTimeImmutable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use Webmozart\Assert\Assert;
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

    public const SORT_BY_UNPUBLISH_DATE = 'unpublishDate';

    /**
     * @var callable
     */
    private $sortBy;

    /**
     * @param Iterator<string, ReadablePage>       $iterator The Iterator to filter
     * @param self::SORT_BY_*|list<string>|Closure $sortBy   the sort type (one of the SORT_BY_* constants),
     *                                                       a PHP closure or
     *                                                       an array holding a SORT_BY_ type and an additional parameter
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
        if (is_array($sortBy)) {
            if (2 !== count($sortBy)) {
                throw new InvalidArgumentException('Arrays not holding a sorting type and parameters are not supported');
            }

            /* @noinspection SuspiciousAssignmentsInspection */
            [$sortBy, $parameter] = $sortBy;
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
            self::SORT_BY_UNPUBLISH_DATE,
        ]);

        $this->configureSortFunction($sortBy, $parameter);
    }

    /**
     * @param self::SORT_BY_* $sortBy
     *
     * @throws InvalidArgumentException
     */
    private function configureSortFunction(string $sortBy, ?string $parameter = null): void
    {
        match ($sortBy) {
            self::SORT_BY_NAME,
            self::SORT_BY_SLUG,
            self::SORT_BY_TITLE,
            self::SORT_BY_EXTRA_VALUE => $this->configureSortByGetterFallbackToPath($sortBy, $parameter),

            self::SORT_BY_DATE,
            self::SORT_BY_PUBLISH_DATE,
            self::SORT_BY_UNPUBLISH_DATE => $this->configureSortByDateOrPath($sortBy),

            self::SORT_BY_PATH,
            self::SORT_BY_FILESYSTEM_PATH => $this->configureSortByPath($sortBy),
        };
    }

    public function getIterator(): Iterator
    {
        $array = iterator_to_array($this->iterator, true);
        uasort($array, $this->sortBy);

        return new ArrayIterator($array);
    }

    /**
     * @param self::SORT_BY_NAME|self::SORT_BY_SLUG|self::SORT_BY_TITLE|self::SORT_BY_EXTRA_VALUE $sortBy
     */
    private function configureSortByGetterFallbackToPath(string $sortBy, ?string $parameter = null): void
    {
        $this->sortBy = function (ReadablePage $pageA, ReadablePage $pageB) use ($sortBy, $parameter): int {
            $parameter ??= '';
            $valueA = $this->matchStringGetter($sortBy, $parameter, $pageA);
            $valueB = $this->matchStringGetter($sortBy, $parameter, $pageB);
            if ($valueA === $valueB) {
                return strcmp($this->matchPathGetter(self::SORT_BY_PATH, $pageA), $this->matchPathGetter(self::SORT_BY_PATH, $pageB));
            }

            return strcmp($valueA, $valueB);
        };
    }

    /**
     * @param self::SORT_BY_DATE|self::SORT_BY_PUBLISH_DATE|self::SORT_BY_UNPUBLISH_DATE $sortBy
     */
    private function configureSortByDateOrPath(string $sortBy): void
    {
        $this->sortBy = function (ReadablePage $pageA, ReadablePage $pageB) use ($sortBy): int {
            $valueA = $this->matchDateGetter($sortBy, $pageA);
            $valueB = $this->matchDateGetter($sortBy, $pageB);
            if ($valueA === $valueB) {
                return strcmp($this->matchPathGetter(self::SORT_BY_PATH, $pageA), $this->matchPathGetter(self::SORT_BY_PATH, $pageB));
            }

            return $valueA <=> $valueB;
        };
    }

    /**
     * @param self::SORT_BY_PATH|self::SORT_BY_FILESYSTEM_PATH $sortBy
     */
    private function configureSortByPath(string $sortBy): void
    {
        $this->sortBy = fn (ReadablePage $pageA, ReadablePage $pageB): int => strcmp($this->matchPathGetter($sortBy, $pageA), $this->matchPathGetter($sortBy, $pageB));
    }

    /**
     * @param self::SORT_BY_NAME|self::SORT_BY_SLUG|self::SORT_BY_TITLE|self::SORT_BY_EXTRA_VALUE $sortBy
     */
    private function matchStringGetter(string $sortBy, string $parameter, ReadablePage $page): string
    {
        return mb_strtolower((string) match ($sortBy) {
            self::SORT_BY_SLUG => $page->getSlug(),
            self::SORT_BY_NAME => $page->getName(),
            self::SORT_BY_TITLE => $page->getTitle(),
            self::SORT_BY_EXTRA_VALUE => is_scalar($page->getExtra($parameter)) ? $page->getExtra($parameter) : '',
        });
    }

    /**
     * @param self::SORT_BY_PATH|self::SORT_BY_FILESYSTEM_PATH $sortBy
     */
    private function matchPathGetter(string $sortBy, ReadablePage $page): string
    {
        return mb_strtolower(match ($sortBy) {
            self::SORT_BY_PATH => $page->getPath()->toString(),
            self::SORT_BY_FILESYSTEM_PATH => $page->getFilesystemPath()->toString(),
        });
    }

    /**
     * @param self::SORT_BY_DATE|self::SORT_BY_PUBLISH_DATE|self::SORT_BY_UNPUBLISH_DATE $sortBy
     */
    private function matchDateGetter(string $sortBy, ReadablePage $page): int
    {
        $date = match ($sortBy) {
            self::SORT_BY_DATE => $page->getDate(),
            self::SORT_BY_PUBLISH_DATE => $page->getPublishDate(),
            self::SORT_BY_UNPUBLISH_DATE => $page->getUnpublishDate(),
        };

        return $date instanceof DateTimeImmutable ? (int) $date->format('U') : 0;
    }
}
