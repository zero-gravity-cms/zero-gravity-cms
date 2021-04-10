<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use Iterator;
use ZeroGravity\Cms\Content\Finder\Tester\TaxonomyTester;
use ZeroGravity\Cms\Content\Page;

/**
 * ModularFilterIterator filters out pages that do not match the required modular state.
 *
 * @method Page current()
 */
class TaxonomiesFilterIterator extends FilterIterator
{
    /**
     * @var TaxonomyTester[]
     */
    private $taxonomies;

    /**
     * @var TaxonomyTester[]
     */
    private $notTaxonomies;

    /**
     * @param Iterator         $iterator      The Iterator to filter
     * @param TaxonomyTester[] $taxonomies
     * @param TaxonomyTester[] $notTaxonomies
     */
    public function __construct(Iterator $iterator, array $taxonomies, array $notTaxonomies)
    {
        $this->taxonomies = $taxonomies;
        $this->notTaxonomies = $notTaxonomies;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        $page = $this->current();
        foreach ($this->taxonomies as $taxonomyTester) {
            if (!$taxonomyTester->pageMatchesTaxonomy($page)) {
                return false;
            }
        }

        foreach ($this->notTaxonomies as $taxonomyTester) {
            if ($taxonomyTester->pageMatchesTaxonomy($page)) {
                return false;
            }
        }

        return true;
    }
}
