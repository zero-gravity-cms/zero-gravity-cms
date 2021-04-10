<?php

namespace ZeroGravity\Cms\Content\Finder;

use AppendIterator;
use ArrayIterator;
use Closure;
use Countable;
use InvalidArgumentException;
use Iterator;
use IteratorAggregate;
use LogicException;
use RecursiveIteratorIterator;
use Symfony\Component\Finder\Iterator\CustomFilterIterator;
use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Content\Finder\Iterator\LimitAndOffsetIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\RecursivePageIterator;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * This PageFinder implementation is heavily inspired by Symfony's Finder component and shares some of its code.
 */
final class PageFinder implements IteratorAggregate, Countable
{
    use PageFinderContentTrait;
    use PageFinderDepthTrait;
    use PageFinderFilesTrait;
    use PageFinderFlagsTrait;
    use PageFinderPathsTrait;
    use PageFinderSettingsTrait;
    use PageFinderSortingTrait;
    use PageFinderTaxonomyTrait;

    private ?int $limit = null;
    private ?int $offset = null;

    /**
     * @var Page[][]
     */
    private array $pageLists = [];

    /**
     * @var callable[]
     */
    private array $filters = [];

    /**
     * @var Iterator[]
     */
    private array $iterators = [];

    /**
     * Creates a new PageFinder for fluent interfaces.
     */
    public static function create(): PageFinder
    {
        return new static();
    }

    public function __construct()
    {
        $this->published(true);
    }

    /**
     * @param Page[]|ReadablePage[] $pages
     */
    public function inPageList(array $pages): self
    {
        $this->pageLists[] = $pages;

        return $this;
    }

    /**
     * Set a finder limit.
     */
    public function limit(int $limit = null): self
    {
        $this->limit = $limit;

        return $this;
    }

    /**
     * Set a finder offset.
     */
    public function offset(int $offset = null): self
    {
        $this->offset = $offset;

        return $this;
    }

    /**
     * @return Page[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this, true);
    }

    /**
     * Filters the iterator with an anonymous function.
     *
     * The anonymous function receives a Page and must return false
     * to remove pages.
     *
     * @see CustomFilterIterator
     */
    public function filter(Closure $closure): self
    {
        $this->filters[] = $closure;

        return $this;
    }

    /**
     * Counts all the results collected by the iterators.
     */
    public function count(): int
    {
        return iterator_count($this->getIterator());
    }

    /**
     * Returns an Iterator for the current Finder configuration.
     *
     * This method implements the IteratorAggregate interface.
     *
     * @return Iterator|Page[] An iterator
     *
     * @throws LogicException if the in() method has not been called
     */
    public function getIterator(): Iterator
    {
        $this->validatePageListsAndIterators();

        if (1 === count($this->pageLists) && 0 === count($this->iterators)) {
            return $this->buildIteratorFromSinglePageList($this->pageLists[0]);
        }

        return $this->buildIteratorFromPageListsAndIterators();
    }

    /**
     * Appends an existing set of pages to the finder.
     *
     * The set can be another PageFinder, an Iterator, an IteratorAggregate, or even a plain array.
     *
     * @throws InvalidArgumentException when the given argument is not iterable
     */
    public function append($iterator): self
    {
        if ($iterator instanceof IteratorAggregate) {
            $this->iterators[] = $iterator->getIterator();
        } elseif ($iterator instanceof Iterator) {
            $this->iterators[] = $iterator;
        } elseif (is_iterable($iterator)) {
            $this->iterators[] = $this->appendPageArrayIterator($iterator);
        } elseif ($iterator instanceof Page) {
            $this->iterators[] = new ArrayIterator([$iterator->getPath()->toString() => $iterator]);
        } else {
            throw new InvalidArgumentException('PageFinder::append() method wrong argument type.');
        }

        return $this;
    }

    private function appendPageArrayIterator($iterator): Iterator
    {
        $pages = [];
        foreach ($iterator as $page) {
            Assert::isInstanceOf($page, Page::class);
            /* @var $page Page */
            $pages[$page->getPath()->toString()] = $page;
        }

        return new ArrayIterator($pages);
    }

    /**
     * Check if any lists or iterators have been set. If not, throw an exception.
     */
    private function validatePageListsAndIterators(): void
    {
        if (0 === count($this->pageLists) && 0 === count($this->iterators)) {
            throw new LogicException('You must call one of inPageList() or append() methods before iterating over a PageFinder.');
        }
    }

    private function buildIteratorFromPageListsAndIterators(): Iterator
    {
        $iterator = new AppendIterator();
        foreach ($this->pageLists as $pageList) {
            $iterator->append($this->buildIteratorFromSinglePageList($pageList));
        }

        foreach ($this->iterators as $it) {
            $iterator->append($it);
        }

        return $iterator;
    }

    private function buildIteratorFromSinglePageList(array $pageList): Iterator
    {
        $mode = RecursiveIteratorIterator::SELF_FIRST;
        $iterator = new RecursiveIteratorIterator(new RecursivePageIterator($pageList), $mode);

        $iterator = $this->applyDepthsIterator($iterator);
        $iterator = $this->applyPublishedIterator($iterator);
        $iterator = $this->applySlugsIterator($iterator);
        $iterator = $this->applyTitlesIterator($iterator);
        $iterator = $this->applyNamesIterator($iterator);
        $iterator = $this->applyPathsIterator($iterator);
        $iterator = $this->applyFilesystemPathsIterator($iterator);
        $iterator = $this->applyExtrasIterator($iterator);
        $iterator = $this->applySettingsIterator($iterator);
        $iterator = $this->applyTaxonomyIterator($iterator);
        $iterator = $this->applyContentTypesIterator($iterator);
        $iterator = $this->applyDatesIterator($iterator);
        $iterator = $this->applyNumberOfFilesIterator($iterator);
        $iterator = $this->applyNumberOfImagesIterator($iterator);
        $iterator = $this->applyNumberOfDocumentsIterator($iterator);
        $iterator = $this->applyFlagsIterator($iterator);
        $iterator = $this->applyContentIterator($iterator);
        $iterator = $this->applyCustomFiltersIterator($iterator);
        $iterator = $this->applySortIterator($iterator);

        return $this->applyOffsetAndLimitIterator($iterator);
    }

    private function applyCustomFiltersIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->filters)) {
            $iterator = new CustomFilterIterator($iterator, $this->filters);
        }

        return $iterator;
    }

    private function applyOffsetAndLimitIterator(Iterator $iterator): Iterator
    {
        if (null !== $this->limit || null !== $this->offset) {
            $aggregate = new LimitAndOffsetIterator($iterator, $this->limit, $this->offset);
            $iterator = $aggregate->getIterator();
        }

        return $iterator;
    }
}
