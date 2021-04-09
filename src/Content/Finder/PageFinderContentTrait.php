<?php

namespace ZeroGravity\Cms\Content\Finder;

use ZeroGravity\Cms\Content\Page;

trait PageFinderContentTrait
{
    private $names = [];
    private $notNames = [];
    private $contains = [];
    private $notContains = [];

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

    private function applyNamesIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->names) || !empty($this->notNames)) {
            $iterator = new Iterator\NameFilterIterator($iterator, $this->names, $this->notNames);
        }

        return $iterator;
    }

    private function applyContentIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->contains) || !empty($this->notContains)) {
            $iterator = new Iterator\ContentFilterIterator($iterator, $this->contains, $this->notContains);
        }

        return $iterator;
    }
}
