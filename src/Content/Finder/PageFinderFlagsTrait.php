<?php

namespace ZeroGravity\Cms\Content\Finder;

use Iterator;
use ZeroGravity\Cms\Content\Finder\Iterator\ModularFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\ModuleFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\PublishedFilterIterator;
use ZeroGravity\Cms\Content\Finder\Iterator\VisibleFilterIterator;

trait PageFinderFlagsTrait
{
    private ?bool $published = null;
    private ?bool $modular = null;
    private ?bool $module = null;
    private ?bool $visible = null;

    /**
     * Restrict to published or unpublished pages.
     *
     * @param bool|null $published true for published pages, false for unpublished, null to ignore setting
     */
    public function published(?bool $published): self
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Restrict to modular or non-modular pages.
     *
     * @param bool|null $modular true for modular pages, false for non-modular, null to ignore setting
     */
    public function modular(?bool $modular): self
    {
        $this->modular = $modular;

        return $this;
    }

    /**
     * Restrict to module or non-module pages.
     *
     * @param bool|null $module true for module pages, false for non-module, null to ignore setting
     */
    public function module(?bool $module): self
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Restrict to visible or hidden pages.
     *
     * @param bool|null $visible true for visible pages, false for hidden, null to ignore setting
     */
    public function visible(?bool $visible): self
    {
        $this->visible = $visible;

        return $this;
    }

    private function applyFlagsIterator(Iterator $iterator): Iterator
    {
        if (null !== $this->modular) {
            $iterator = new ModularFilterIterator($iterator, $this->modular);
        }

        if (null !== $this->module) {
            $iterator = new ModuleFilterIterator($iterator, $this->module);
        }

        if (null !== $this->visible) {
            $iterator = new VisibleFilterIterator($iterator, $this->visible);
        }

        return $iterator;
    }

    private function applyPublishedIterator(Iterator $iterator): Iterator
    {
        if (null !== $this->published) {
            $iterator = new PublishedFilterIterator($iterator, $this->published);
        }

        return $iterator;
    }
}
