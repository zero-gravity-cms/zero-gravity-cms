<?php

namespace ZeroGravity\Cms\Content\Finder;

use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;

trait PageFinderTaxonomyTrait
{
    private $taxonomies = [];
    private $notTaxonomies = [];

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
     * @param \Iterator $iterator
     *
     * @return \Iterator
     */
    private function applyTaxonomyIterator(\Iterator $iterator): \Iterator
    {
        if (!empty($this->taxonomies) || !empty($this->notTaxonomies)) {
            $iterator = new Iterator\TaxonomiesFilterIterator($iterator, $this->taxonomies, $this->notTaxonomies);
        }

        return $iterator;
    }
}
