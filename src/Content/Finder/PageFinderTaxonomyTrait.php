<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use ZeroGravity\Cms\Content\Finder\Iterator\TaxonomiesFilterIterator;
use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;

trait PageFinderTaxonomyTrait
{
    /**
     * @var list<TaxonomyTester>
     */
    private array $taxonomies = [];

    /**
     * Add taxonomies that pages must provide.
     *
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     * @param string|list<string>             $values
     */
    public function taxonomy(string $name, string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        $this->taxonomies[] = TaxonomyTester::has($name, (array) $values, $operator);

        return $this;
    }

    /**
     * Add taxonomies that pages must not provide.
     *
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     * @param string|list<string>             $values
     */
    public function notTaxonomy(string $name, string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        $this->taxonomies[] = TaxonomyTester::hasNot($name, (array) $values, $operator);

        return $this;
    }

    /**
     * Add tag or tags that pages must provide.
     *
     * @param string|array<string>            $values
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function tag(string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->taxonomy(Page::TAXONOMY_TAG, $values, $operator);
    }

    /**
     * Add tag or tags that pages must provide.
     *
     * @param string|array<string>            $values
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function notTag(string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->notTaxonomy(Page::TAXONOMY_TAG, $values, $operator);
    }

    /**
     * Add category or categories that pages must provide.
     *
     * @param string|array<string>            $values
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function category(string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->taxonomy(Page::TAXONOMY_CATEGORY, $values, $operator);
    }

    /**
     * Add category or categories that pages must not provide.
     *
     * @param string|array<string>            $values
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function notCategory(string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->notTaxonomy(Page::TAXONOMY_CATEGORY, $values, $operator);
    }

    /**
     * Add author or authors that pages must provide.
     *
     * @param string|array<string>            $values
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function author(string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->taxonomy(Page::TAXONOMY_AUTHOR, $values, $operator);
    }

    /**
     * Add author or authors that pages must not provide.
     *
     * @param string|array<string>            $values
     * @param TaxonomyTester::OPERATOR_*|null $operator 'AND' or 'OR'. Only applies to this set of taxonomies.
     */
    public function notAuthor(string|array $values, ?string $operator = TaxonomyTester::OPERATOR_AND): self
    {
        return $this->notTaxonomy(Page::TAXONOMY_AUTHOR, $values, $operator);
    }

    /**
     * @param Iterator<string, ReadablePage> $iterator
     *
     * @return Iterator<string, ReadablePage>
     */
    private function applyTaxonomyIterator(Iterator $iterator): Iterator
    {
        if ([] === $this->taxonomies) {
            return $iterator;
        }

        return new TaxonomiesFilterIterator($iterator, $this->taxonomies);
    }
}
