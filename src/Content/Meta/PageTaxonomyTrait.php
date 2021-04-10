<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Meta;

use ZeroGravity\Cms\Content\Page;

/**
 * This trait contains settings getters related to page taxonomy.
 */
trait PageTaxonomyTrait
{
    abstract public function getSetting(string $name);

    /**
     * Get all defined taxonomy keys and values.
     */
    public function getTaxonomies(): array
    {
        return $this->getSetting('taxonomy');
    }

    /**
     * Get values for a single taxonomy key.
     */
    public function getTaxonomy(string $name): array
    {
        $taxonomy = $this->getSetting('taxonomy');
        if (isset($taxonomy[$name])) {
            return (array) $taxonomy[$name];
        }

        return [];
    }

    public function getTags(): array
    {
        return $this->getTaxonomy(Page::TAXONOMY_TAG);
    }

    public function getCategories(): array
    {
        return $this->getTaxonomy(Page::TAXONOMY_CATEGORY);
    }

    public function getAuthors(): array
    {
        return $this->getTaxonomy(Page::TAXONOMY_AUTHOR);
    }
}
