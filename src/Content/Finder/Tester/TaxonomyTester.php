<?php

namespace ZeroGravity\Cms\Content\Finder\Tester;

use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Page;

class TaxonomyTester
{
    /**
     * @var string
     */
    private $name;

    /**
     * @var array
     */
    private $values;

    /**
     * @var string
     */
    private $mode;

    public function __construct($name, array $values, $mode)
    {
        $this->name = $name;
        $this->values = $values;
        $this->mode = $mode;
    }

    /**
     * Return true if value matches the taxonomies to test against, false if not.
     *
     * @param Page $page
     *
     * @return bool
     */
    public function pageMatchesTaxonomy(Page $page): bool
    {
        $pageValues = $page->getTaxonomy($this->name);

        if (PageFinder::TAXONOMY_OR === $this->mode) {
            foreach ($this->values as $value) {
                if (in_array($value, $pageValues)) {
                    return true;
                }
            }

            return false;
        }

        foreach ($this->values as $value) {
            if (!in_array($value, $pageValues)) {
                return false;
            }
        }

        return true;
    }
}
