<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use Symfony\Component\Finder\Comparator;
use Symfony\Component\Finder\Comparator\DateComparator;
use ZeroGravity\Cms\Content\Finder\Iterator\ContentTypeFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\DateRangeFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\ExtraFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SettingFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\SlugFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\TitleFilterIterator;

trait PageFinderSettingsTrait
{
    private $dates = [];
    private $slugs = [];
    private $notSlugs = [];
    private $titles = [];
    private $notTitles = [];
    private $extras = [];
    private $notExtras = [];
    private $settings = [];
    private $notSettings = [];
    private $contentTypes = [];
    private $notContentTypes = [];

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
        $this->dates[] = new DateComparator($date);

        return $this;
    }

    /**
     * Adds rules that pages extra setting values must match.
     *
     * $finder->extra('my_extra', 'value')
     *
     * @param string $name
     * @param string $comparator One of the ExtraFilterIterator::COMPARATOR_* constants
     *
     * @return $this
     *
     * @see ExtraFilterIterator
     */
    public function extra($name, $value, $comparator = ExtraFilterIterator::COMPARATOR_STRING)
    {
        $this->extras[] = [$name, $value, $comparator];

        return $this;
    }

    /**
     * Adds rules that pages extra setting values must not match.
     *
     * $finder->notExtra('my_extra', 'value')
     *
     * @param string $name
     * @param string $comparator One of the ExtraFilterIterator::COMPARATOR_* constants
     *
     * @return $this
     *
     * @see ExtraFilterIterator
     */
    public function notExtra($name, $value, $comparator = ExtraFilterIterator::COMPARATOR_STRING)
    {
        $this->notExtras[] = [$name, $value, $comparator];

        return $this;
    }

    /**
     * Adds rules that pages setting values must match.
     *
     * $finder->setting('my_setting', 'value')
     *
     * @param string $name
     *
     * @return $this
     */
    public function setting($name, $value)
    {
        $this->settings[] = [$name, $value];

        return $this;
    }

    /**
     * Adds rules that pages setting values must not match.
     *
     * $finder->notSetting('my_setting', 'value')
     *
     * @param string $name
     *
     * @return $this
     */
    public function notSetting($name, $value)
    {
        $this->notSettings[] = [$name, $value];

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
     * @return $this
     *
     * @see ContentTypeFilterIterator
     */
    public function contentType($pattern)
    {
        $this->contentTypes[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @return $this
     *
     * @see ContentTypeFilterIterator
     */
    public function notContentType($pattern)
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
        if (!empty($this->extras) || !empty($this->notExtras)) {
            $iterator = new ExtraFilterIterator($iterator, $this->extras, $this->notExtras);
        }

        return $iterator;
    }

    private function applySettingsIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->settings) || !empty($this->notSettings)) {
            $iterator = new SettingFilterIterator($iterator, $this->settings, $this->notSettings);
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
