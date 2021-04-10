<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use ZeroGravity\Cms\Content\Finder\Iterator\TaxonomiesFilterIterator;
use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;

trait PageFinderTaxonomyTrait
{
    /**
     * @var TaxonomyTester[]
     */
    private array $taxonomies = [];

    /**
     * Add taxonomies that pages must provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function taxonomy($name, $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        $this->taxonomies[] = TaxonomyTester::has($name, (array) $values, $operator);

        return $this;
    }

    /**
     * Add taxonomies that pages must not provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function notTaxonomy($name, $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        $this->taxonomies[] = TaxonomyTester::hasNot($name, (array) $values, $operator);

        return $this;
    }

    /**
     * Add tag or tags that pages must provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function tag($values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->taxonomy(Page::TAXONOMY_TAG, $values, $operator);
    }

    /**
     * Add tag or tags that pages must provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function notTag($values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->notTaxonomy(Page::TAXONOMY_TAG, $values, $operator);
    }

    /**
     * Add category or categories that pages must provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function category($values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->taxonomy(Page::TAXONOMY_CATEGORY, $values, $operator);
    }

    /**
     * Add category or categories that pages must not provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function notCategory($values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->notTaxonomy(Page::TAXONOMY_CATEGORY, $values, $operator);
    }

    /**
     * Add author or authors that pages must provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function author($values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->taxonomy(Page::TAXONOMY_AUTHOR, $values, $operator);
    }

    /**
     * Add author or authors that pages must not provide.
     *
     * @param string|array $values
     * @param string|null  $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function notAuthor($values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->notTaxonomy(Page::TAXONOMY_AUTHOR, $values, $operator);
    }

    private function applyTaxonomyIterator(Iterator $iterator): Iterator
    {
        if (!empty($this->taxonomies) || !empty($this->notTaxonomies)) {
            $iterator = new TaxonomiesFilterIterator($iterator, $this->taxonomies);
        }

        return $iterator;
    }
}
