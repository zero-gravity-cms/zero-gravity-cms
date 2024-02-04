<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use Iterator;
use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;

/**
 * TaxonomiesFilterIterator filters out pages that do not match the required taxonomies.
 *
 * @method Page current()
 */
final class TaxonomiesFilterIterator extends FilterIterator
{
    /**
     * @param Iterator         $iterator   The Iterator to filter
     * @param TaxonomyTester[] $taxonomies
     */
    public function __construct(
        Iterator $iterator,
        private readonly array $taxonomies,
    ) {
        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept(): bool
    {
        $page = $this->current();
        foreach ($this->taxonomies as $taxonomyTester) {
            $isInverted = $taxonomyTester->isInverted();
            $valuesMatch = $taxonomyTester->pageMatchesTaxonomy($page);

            if ($isInverted === $valuesMatch) {
                return false;
            }
        }

        return true;
    }
}
