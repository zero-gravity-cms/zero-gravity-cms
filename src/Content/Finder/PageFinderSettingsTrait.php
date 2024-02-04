<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use Symfony\Component\Finder\Comparator\DateComparator;
use ZeroGravity\Cms\Content\Finder\Iterator\ContentTypeFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\DateRangeFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\ExtraFilter;
use ZeroGravity\Cms\Content\Finder\Iterator\ExtraFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SettingFilter;
use ZeroGravity\Cms\Content\Finder\Iterator\SettingFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SlugFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\TitleFilterIterator;
use ZeroGravity\Cms\Content\ReadablePage;

trait PageFinderSettingsTrait
{
    /**
     * @var list<ExtraFilter>
     */
    private array $extras = [];
    /**
     * @var list<SettingFilter>
     */
    private array $settings = [];
    /**
     * @var list<DateComparator>
     */
    private array $dates = [];
    /**
     * @var list<string>
     */
    private array $slugs = [];
    /**
     * @var list<string>
     */
    private array $notSlugs = [];
    /**
     * @var list<string>
     */
    private array $titles = [];
    /**
     * @var list<string>
     */
    private array $notTitles = [];
    /**
     * @var list<string>
     */
    private array $contentTypes = [];
    /**
     * @var list<string>
     */
    private array $notContentTypes = [];

    /**
     * Adds rules that pages extra setting values must match.
     *
     * $finder->extra('my_extra', 'value')
     *
     * @param string $comparator One of the ExtraFilter::COMPARATOR_* constants
     *
     * @see ExtraFilterIterator
     */
    public function extra(string $name, mixed $value, string $comparator = ExtraFilter::COMPARATOR_STRING): self
    {
        $this->extras[] = ExtraFilter::has($name, $value, $comparator);

        return $this;
    }

    /**
     * Adds rules that pages extra setting values must not match.
     *
     * $finder->notExtra('my_extra', 'value')
     *
     * @param string $comparator One of the ExtraFilter::COMPARATOR_* constants
     *
     * @see ExtraFilterIterator
     */
    public function notExtra(string $name, mixed $value, string $comparator = ExtraFilter::COMPARATOR_STRING): self
    {
        $this->extras[] = ExtraFilter::hasNot($name, $value, $comparator);

        return $this;
    }

    /**
     * Adds rules that pages setting values must match.
     *
     * $finder->setting('my_setting', 'value')
     *
     * @see SettingFilterIterator
     */
    public function setting(string $name, mixed $value): self
    {
        $this->settings[] = SettingFilter::has($name, $value);

        return $this;
    }

    /**
     * Adds rules that pages setting values must not match.
     *
     * $finder->notSetting('my_setting', 'value')
     *
     * @see SettingFilterIterator
     */
    public function notSetting(string $name, mixed $value): self
    {
        $this->settings[] = SettingFilter::hasNot($name, $value);

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
     * @see strtotime
     * @see DateRangeFilterIterator
     * @see DateComparator
     */
    public function date(string $date): self
    {
        $this->dates[] = new DateComparator($date);

        return $this;
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
     * @see SlugFilterIterator
     */
    public function slug(string $pattern): self
    {
        $this->slugs[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @see SlugFilterIterator
     */
    public function notSlug(string $pattern): self
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
     * @see TitleFilterIterator
     */
    public function title(string $pattern): self
    {
        $this->titles[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @see TitleFilterIterator
     */
    public function notTitle(string $pattern): self
    {
        $this->notTitles[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must match.
     *
     * You can use patterns (delimited with / sign), globs or simple strings.
     *
     * $finder->contentType('foo type')
     * $finder->contentType('foo *')
     * $finder->contentType('/foo [a-z]{1,4}/')
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @see ContentTypeFilterIterator
     */
    public function contentType(string $pattern): self
    {
        $this->contentTypes[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @see ContentTypeFilterIterator
     */
    public function notContentType(string $pattern): self
    {
        $this->notContentTypes[] = $pattern;

        return $this;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applySlugsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->slugs) || !empty($this->notSlugs)) {
            return new SlugFilterIterator($iterator, $this->slugs, $this->notSlugs);
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyTitlesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->titles) || !empty($this->notTitles)) {
            return new TitleFilterIterator($iterator, $this->titles, $this->notTitles);
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyExtrasIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->extras)) {
            return new ExtraFilterIterator($iterator, $this->extras);
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applySettingsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->settings)) {
            return new SettingFilterIterator($iterator, $this->settings);
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyContentTypesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->contentTypes) || !empty($this->notContentTypes)) {
            return new ContentTypeFilterIterator($iterator, $this->contentTypes, $this->notContentTypes);
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyDatesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->dates)) {
            return new DateRangeFilterIterator($iterator, $this->dates);
        }

        return $iterator;
    }
}
