<?php

namespace ZeroGravity\Cms\Content\Finder;

use Symfony\Component\Finder\Comparator;
use Symfony\Component\Finder\Iterator\DepthRangeFilterIterator;
use Webmozart\Assert\Assert;
use ZeroGravity\Cms\Content\Finder\Iterator\RecursivePageIterator;
use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;

/**
 * This PageFinder implementation is heavily inspired by Symfony's Finder component and shares some if its code.
 */
class PageFinder implements \IteratorAggregate, \Countable
{
    const TAXONOMY_AND = 'AND';
    const TAXONOMY_OR = 'OR';

    private $published;
    private $modular;
    private $module;
    private $visible;
    private $names = [];
    private $notNames = [];
    private $slugs = [];
    private $notSlugs = [];
    private $titles = [];
    private $notTitles = [];
    private $filters = [];
    private $depths = [];
    private $sort = false;
    private $pageLists = [];
    private $dates = [];
    private $iterators = [];
    private $contains = [];
    private $notContains = [];
    private $paths = [];
    private $notPaths = [];
    private $filesystemPaths = [];
    private $notFilesystemPaths = [];
    private $taxonomies = [];
    private $notTaxonomies = [];

    /**
     * Creates a new Finder.
     *
     * @return static
     */
    public static function create()
    {
        return new static();
    }

    public function __construct()
    {
        $this->published(true);
    }

    /**
     * Restrict to published or unpublished pages.
     *
     * @param bool|null $published true for published pages, false for unpublished, null to ignore setting
     *
     * @return $this
     */
    public function published($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Restrict to modular or non-modular pages.
     *
     * @param bool|null $modular true for modular pages, false for non-modular, null to ignore setting
     *
     * @return $this
     */
    public function modular($modular)
    {
        $this->modular = $modular;

        return $this;
    }

    /**
     * Restrict to module or non-module pages.
     *
     * @param bool|null $module true for module pages, false for non-module, null to ignore setting
     *
     * @return $this
     */
    public function module($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Restrict to visible or hidden pages.
     *
     * @param bool|null $visible true for visible pages, false for hidden, null to ignore setting
     *
     * @return $this
     */
    public function visible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    /**
     * Adds tests for the directory depth.
     *
     * Usage:
     *
     *   $finder->depth('> 1') // the Finder will start matching at level 1.
     *   $finder->depth('< 3') // the Finder will descend at most 3 levels of directories below the starting point.
     *
     * @param string|int $level The depth level expression
     *
     * @return $this
     *
     * @see DepthRangeFilterIterator
     * @see NumberComparator
     */
    public function depth($level)
    {
        $this->depths[] = new Comparator\NumberComparator($level);

        return $this;
    }

    /**
     * Adds tests for page dates (if defined in page metadata).
     *
     * The date must be something that strtotime() is able to parse:
     *
     *   $finder->date('since yesterday');
     *   $finder->date('until 2 days ago');
     *   $finder->date('> now - 2 hours');
     *   $finder->date('>= 2005-10-15');
     *
     * @param string $date A date range string
     *
     * @return $this
     *
     * @see strtotime
     * @see DateRangeFilterIterator
     * @see DateComparator
     */
    public function date($date)
    {
        $this->dates[] = new Comparator\DateComparator($date);

        return $this;
    }

    /**
     * Adds rules that pages must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     * $finder->name('*.php')
     * $finder->name('/\.php$/') // same as above
     * $finder->name('test.php')
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @return $this
     *
     * @see NameFilterIterator
     */
    public function name($pattern)
    {
        $this->names[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @return $this
     *
     * @see NameFilterIterator
     */
    public function notName($pattern)
    {
        $this->notNames[] = $pattern;

        return $this;
    }

    /**
     * Add taxonomies that pages must provide.
     *
     * @param string       $name
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function taxonomy($name, $values, $mode = self::TAXONOMY_AND)
    {
        $this->taxonomies[] = new TaxonomyTester($name, (array) $values, $mode);

        return $this;
    }

    /**
     * Add taxonomies that pages must not provide.
     *
     * @param string       $name
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function notTaxonomy($name, $values, $mode = self::TAXONOMY_AND)
    {
        $this->notTaxonomies[] = new TaxonomyTester($name, (array) $values, $mode);

        return $this;
    }

    /**
     * Add tag or tags that pages must provide.
     *
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function tag($values, $mode = self::TAXONOMY_AND)
    {
        return $this->taxonomy(Page::TAXONOMY_TAG, $values, $mode);
    }

    /**
     * Add tag or tags that pages must provide.
     *
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function notTag($values, $mode = self::TAXONOMY_AND)
    {
        return $this->notTaxonomy(Page::TAXONOMY_TAG, $values, $mode);
    }

    /**
     * Add category or categories that pages must provide.
     *
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function category($values, $mode = self::TAXONOMY_AND)
    {
        return $this->taxonomy(Page::TAXONOMY_CATEGORY, $values, $mode);
    }

    /**
     * Add category or categories that pages must provide.
     *
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function notCategory($values, $mode = self::TAXONOMY_AND)
    {
        return $this->notTaxonomy(Page::TAXONOMY_CATEGORY, $values, $mode);
    }

    /**
     * Add author or authors that pages must provide.
     *
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function author($values, $mode = self::TAXONOMY_AND)
    {
        return $this->taxonomy(Page::TAXONOMY_AUTHOR, $values, $mode);
    }

    /**
     * Add author or authors that pages must provide.
     *
     * @param string|array $values
     * @param string       $mode   'AND' or 'OR'. Only applies to this set of taxonomies.
     *
     * @return $this
     */
    public function notAuthor($values, $mode = self::TAXONOMY_AND)
    {
        return $this->notTaxonomy(Page::TAXONOMY_AUTHOR, $values, $mode);
    }

    /**
     * Adds rules that pages must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     * $finder->slug('*.php')
     * $finder->slug('/\.php$/') // same as above
     * $finder->slug('test.php')
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @return $this
     *
     * @see SlugFilterIterator
     */
    public function slug($pattern)
    {
        $this->slugs[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @return $this
     *
     * @see SlugFilterIterator
     */
    public function notSlug($pattern)
    {
        $this->notSlugs[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     * $finder->title('foo title')
     * $finder->title('foo *')
     * $finder->title('/foo [a-z]{1,4}/')
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @return $this
     *
     * @see TitleFilterIterator
     */
    public function title($pattern)
    {
        $this->titles[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @return $this
     *
     * @see TitleFilterIterator
     */
    public function notTitle($pattern)
    {
        $this->notTitles[] = $pattern;

        return $this;
    }

    /**
     * Adds tests that page contents must match.
     * This will be matched against the raw (HTML or markdown) content.
     *
     * Strings or PCRE patterns can be used:
     *
     * $finder->contains('Lorem ipsum')
     * $finder->contains('/Lorem ipsum/i')
     *
     * @param string $pattern A pattern (string or regexp)
     *
     * @return $this
     *
     * @see FilecontentFilterIterator
     */
    public function contains($pattern)
    {
        $this->contains[] = $pattern;

        return $this;
    }

    /**
     * Adds tests that page contents must not match.
     * This will be matched against the raw (HTML or markdown) content.
     *
     * Strings or PCRE patterns can be used:
     *
     * $finder->notContains('Lorem ipsum')
     * $finder->notContains('/Lorem ipsum/i')
     *
     * @param string $pattern A pattern (string or regexp)
     *
     * @return $this
     *
     * @see FilecontentFilterIterator
     */
    public function notContains($pattern)
    {
        $this->notContains[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that page paths must match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->path('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function path($pattern)
    {
        $this->paths[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that page paths must not match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->notPath('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function notPath($pattern)
    {
        $this->notPaths[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that page filesystem paths must match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->path('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function filesystemPath($pattern)
    {
        $this->filesystemPaths[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that page filesystem paths must not match.
     *
     * You can use patterns (delimited with / sign) or simple strings.
     *
     * $finder->notPath('/some/special/dir/')
     *
     * Use only / as dirname separator.
     *
     * @param string $pattern A pattern (a regexp or a string)
     *
     * @return $this
     *
     * @see FilenameFilterIterator
     */
    public function notFilesystemPath($pattern)
    {
        $this->notFilesystemPaths[] = $pattern;

        return $this;
    }

    /**
     * @param Page[] $pages
     *
     * @return $this
     */
    public function inPageList(array $pages)
    {
        $this->pageLists[] = $pages;

        return $this;
    }

    /**
     * Appends an existing set of pages to the finder.
     *
     * The set can be another PageFinder, an Iterator, an IteratorAggregate, or even a plain array.
     *
     * @param mixed $iterator
     *
     * @return $this
     *
     * @throws \InvalidArgumentException when the given argument is not iterable
     */
    public function append($iterator)
    {
        if ($iterator instanceof \IteratorAggregate) {
            $this->iterators[] = $iterator->getIterator();
        } elseif ($iterator instanceof \Iterator) {
            $this->iterators[] = $iterator;
        } elseif ($iterator instanceof \Traversable || is_array($iterator)) {
            $it = new \ArrayIterator();
            foreach ($iterator as $page) {
                Assert::isInstanceOf($page, Page::class);
                $it->append($page);
            }
            $this->iterators[] = $it;
        } elseif ($iterator instanceof Page) {
            $it = new \ArrayIterator();
            $it->append($iterator);
            $this->iterators[] = $it;
        } else {
            throw new \InvalidArgumentException('PageFinder::append() method wrong argument type.');
        }

        return $this;
    }

    /**
     * Counts all the results collected by the iterators.
     *
     * @return int
     */
    public function count()
    {
        return iterator_count($this->getIterator());
    }

    /**
     * Returns an Iterator for the current Finder configuration.
     *
     * This method implements the IteratorAggregate interface.
     *
     * @return \Iterator|Page[] An iterator
     *
     * @throws \LogicException if the in() method has not been called
     */
    public function getIterator()
    {
        if (0 === count($this->pageLists) && 0 === count($this->iterators)) {
            throw new \LogicException('You must call one of inPageList() or append() methods before iterating over a PageFinder.');
        }

        if (1 === count($this->pageLists) && 0 === count($this->iterators)) {
            return $this->searchInPageList($this->pageLists[0]);
        }

        $iterator = new \AppendIterator();
        foreach ($this->pageLists as $pageList) {
            $iterator->append($this->searchInPageList($pageList));
        }

        foreach ($this->iterators as $it) {
            $iterator->append($it);
        }

        return $iterator;
    }

    /**
     * @param array $pageList
     *
     * @return \Iterator
     */
    private function searchInPageList(array $pageList)
    {
        $mode = \RecursiveIteratorIterator::SELF_FIRST;
        $iterator = new \RecursiveIteratorIterator(new RecursivePageIterator($pageList), $mode);

        $minDepth = 0;
        $maxDepth = PHP_INT_MAX;

        foreach ($this->depths as $comparator) {
            switch ($comparator->getOperator()) {
                case '>':
                    $minDepth = $comparator->getTarget() + 1;
                    break;
                case '>=':
                    $minDepth = $comparator->getTarget();
                    break;
                case '<':
                    $maxDepth = $comparator->getTarget() - 1;
                    break;
                case '<=':
                    $maxDepth = $comparator->getTarget();
                    break;
                default:
                    $minDepth = $maxDepth = $comparator->getTarget();
            }
        }

        if ($minDepth > 0 || $maxDepth < PHP_INT_MAX) {
            $iterator = new DepthRangeFilterIterator($iterator, $minDepth, $maxDepth);
        }

        if ($this->names || $this->notNames) {
            $iterator = new Iterator\NameFilterIterator($iterator, $this->names, $this->notNames);
        }

        if ($this->slugs || $this->notSlugs) {
            $iterator = new Iterator\SlugFilterIterator($iterator, $this->slugs, $this->notSlugs);
        }

        if ($this->titles || $this->notTitles) {
            $iterator = new Iterator\TitleFilterIterator($iterator, $this->titles, $this->notTitles);
        }

        if ($this->paths || $this->notPaths) {
            $iterator = new Iterator\PathFilterIterator($iterator, $this->paths, $this->notPaths);
        }

        if ($this->filesystemPaths || $this->notFilesystemPaths) {
            $iterator = new Iterator\FilesystemPathFilterIterator(
                $iterator,
                $this->filesystemPaths,
                $this->notFilesystemPaths
            );
        }

        if ($this->taxonomies || $this->notTaxonomies) {
            $iterator = new Iterator\TaxonomiesFilterIterator($iterator, $this->taxonomies, $this->notTaxonomies);
        }

        if ($this->contains || $this->notContains) {
            $iterator = new Iterator\ContentFilterIterator($iterator, $this->contains, $this->notContains);
        }

        if ($this->dates) {
            $iterator = new Iterator\DateRangeFilterIterator($iterator, $this->dates);
        }

        if (null !== $this->modular) {
            $iterator = new Iterator\ModularFilterIterator($iterator, $this->modular);
        }

        if (null !== $this->module) {
            $iterator = new Iterator\ModuleFilterIterator($iterator, $this->module);
        }

        if (null !== $this->visible) {
            $iterator = new Iterator\VisibleFilterIterator($iterator, $this->visible);
        }

        if (null !== $this->published) {
            $iterator = new Iterator\PublishedFilterIterator($iterator, $this->published);
        }

        return $iterator;
        /*
        if (static::IGNORE_VCS_FILES === (static::IGNORE_VCS_FILES & $this->ignore)) {
            $this->exclude = array_merge($this->exclude, self::$vcsPatterns);
        }

        if (static::IGNORE_DOT_FILES === (static::IGNORE_DOT_FILES & $this->ignore)) {
            $this->notPaths[] = '#(^|/)\..+(/|$)#';
        }

        $flags = \RecursiveDirectoryIterator::SKIP_DOTS;

        $iterator = new Iterator\RecursiveDirectoryIterator($pageList, $flags, $this->ignoreUnreadableDirs);

        if ($this->exclude) {
            $iterator = new Iterator\ExcludeDirectoryFilterIterator($iterator, $this->exclude);
        }

        $iterator = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::SELF_FIRST);

        if ($this->sizes) {
            $iterator = new Iterator\SizeRangeFilterIterator($iterator, $this->sizes);
        }

        if ($this->filters) {
            $iterator = new Iterator\CustomFilterIterator($iterator, $this->filters);
        }

        if ($this->sort) {
            $iteratorAggregate = new Iterator\SortableIterator($iterator, $this->sort);
            $iterator = $iteratorAggregate->getIterator();
        }

        return $iterator;
        */
    }

    /**
     * @return Page[]
     */
    public function toArray(): array
    {
        return iterator_to_array($this, false);
    }
}
