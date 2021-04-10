<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use Symfony\Component\Finder\Comparator;
use Symfony\Component\Finder\Comparator\DateComparator;
use ZeroGravity\Cms\Content\Finder\Iterator\ContentTypeFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\DateRangeFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\ExtraFilter;
use ZeroGravity\Cms\Content\Finder\Iterator\ExtraFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SettingFilter;
use ZeroGravity\Cms\Content\Finder\Iterator\SettingFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SlugFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\TitleFilterIterator;

trait PageFinderSettingsTrait
{
    /**
     * @var ExtraFilter[]
     */
    private array $extras = [];
    /**
     * @var SettingFilter[]
     */
    private array $settings = [];
    /**
     * @var DateComparator[]
     */
    private array $dates = [];
    /**
     * @var string[]
     */
    private array $slugs = [];
    /**
     * @var string[]
     */
    private array $notSlugs = [];
    /**
     * @var string[]
     */
    private array $titles = [];
    /**
     * @var string[]
     */
    private array $notTitles = [];
    /**
     * @var string[]
     */
    private array $contentTypes = [];
    /**
     * @var string[]
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
    public function extra(string $name, $value, string $comparator = ExtraFilter::COMPARATOR_STRING): self
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
    public function notExtra(string $name, $value, string $comparator = ExtraFilter::COMPARATOR_STRING): self
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
    public function setting(string $name, $value): self
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
    public function notSetting(string $name, $value): self
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

    private function applySlugsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->slugs) || !empty($this->notSlugs)) {
            $iterator = new SlugFilterIterator($iterator, $this->slugs, $this->notSlugs);
        }

        return $iterator;
    }

    private function applyTitlesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->titles) || !empty($this->notTitles)) {
            $iterator = new TitleFilterIterator($iterator, $this->titles, $this->notTitles);
        }

        return $iterator;
    }

    private function applyExtrasIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->extras)) {
            $iterator = new ExtraFilterIterator($iterator, $this->extras);
        }

        return $iterator;
    }

    private function applySettingsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->settings)) {
            $iterator = new SettingFilterIterator($iterator, $this->settings);
        }

        return $iterator;
    }

    private function applyContentTypesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->contentTypes) || !empty($this->notContentTypes)) {
            $iterator = new ContentTypeFilterIterator($iterator, $this->contentTypes, $this->notContentTypes);
        }

        return $iterator;
    }

    private function applyDatesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->dates)) {
            $iterator = new DateRangeFilterIterator($iterator, $this->dates);
        }

        return $iterator;
    }
}
