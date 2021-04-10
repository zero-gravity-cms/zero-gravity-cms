<?php

namespace ZeroGravity\Cms\Content\Finder\Tester;

use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Page;

class TaxonomyTester
{
    private string $name;

    private array $values;

    private string $mode;

    public function __construct($name, array $values, ?string $mode)
    {
        $mode ??= PageFinder::TAXONOMY_AND;
        $this->name = $name;
        $this->values = $values;
        $this->mode = $mode;
    }

    /**
     * Return true if value matches the taxonomies to test against, false if not.
     */
    public function pageMatchesTaxonomy(Page $page): bool
    {
        $pageValues = $page->getTaxonomy($this->name);

        if (PageFinder::TAXONOMY_OR === $this->mode) {
            return $this->testOr($pageValues);
        }

        return $this->testAnd($pageValues);
    }

    /**
     * @param $pageValues
     */
    private function testOr($pageValues): bool
    {
        foreach ($this->values as $value) {
            if (in_array($value, $pageValues)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param $pageValues
     */
    private function testAnd($pageValues): bool
    {
        foreach ($this->values as $value) {
            if (!in_array($value, $pageValues)) {
                return false;
            }
        }

        return true;
    }
}
