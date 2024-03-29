<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use ZeroGravity\Cms\Content\Finder\Iterator\ContentFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\NameFilterIterator;
use ZeroGravity\Cms\Content\ReadablePage;

trait PageFinderContentTrait
{
    /**
     * @var list<string>
     */
    private array $names = [];
    /**
     * @var list<string>
     */
    private array $notNames = [];
    /**
     * @var list<string>
     */
    private array $contains = [];
    /**
     * @var list<string>
     */
    private array $notContains = [];

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
     * @see NameFilterIterator
     */
    public function name(string $pattern): self
    {
        $this->names[] = $pattern;

        return $this;
    }

    /**
     * Adds rules that pages must not match.
     *
     * @param string $pattern A pattern (a regexp, a glob, or a string)
     *
     * @see NameFilterIterator
     */
    public function notName(string $pattern): self
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
     * @see ContentFilterIterator
     */
    public function contains(string $pattern): self
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
     * @see ContentFilterIterator
     */
    public function notContains(string $pattern): self
    {
        $this->notContains[] = $pattern;

        return $this;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyNamesIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->names) || !empty($this->notNames)) {
            return new NameFilterIterator($iterator, $this->names, $this->notNames);
        }

        return $iterator;
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyContentIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->contains) || !empty($this->notContains)) {
            return new ContentFilterIterator($iterator, $this->contains, $this->notContains);
        }

        return $iterator;
    }
}
