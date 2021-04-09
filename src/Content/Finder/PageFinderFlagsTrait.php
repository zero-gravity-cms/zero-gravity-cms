<?php

namespace ZeroGravity\Cms\Content\Finder;

trait PageFinderFlagsTrait
{
    private $published;
    private $modular;
    private $module;
    private $visible;

    /**
     * Restrict to published or unpublished pages.
     *
     * @param bool|null $published true for published pages, false for unpublished, null to ignore setting
     *
     * @return $this
     */
    public function published($published)
    {
        $this->published = $published;

        return $this;
    }

    /**
     * Restrict to modular or non-modular pages.
     *
     * @param bool|null $modular true for modular pages, false for non-modular, null to ignore setting
     *
     * @return $this
     */
    public function modular($modular)
    {
        $this->modular = $modular;

        return $this;
    }

    /**
     * Restrict to module or non-module pages.
     *
     * @param bool|null $module true for module pages, false for non-module, null to ignore setting
     *
     * @return $this
     */
    public function module($module)
    {
        $this->module = $module;

        return $this;
    }

    /**
     * Restrict to visible or hidden pages.
     *
     * @param bool|null $visible true for visible pages, false for hidden, null to ignore setting
     *
     * @return $this
     */
    public function visible($visible)
    {
        $this->visible = $visible;

        return $this;
    }

    private function applyFlagsIterator(\Iterator $iterator): \Iterator
    {
        if (null !== $this->modular) {
            $iterator = new Iterator\ModularFilterIterator($iterator, $this->modular);
        }

        if (null !== $this->module) {
            $iterator = new Iterator\ModuleFilterIterator($iterator, $this->module);
        }

        if (null !== $this->visible) {
            $iterator = new Iterator\VisibleFilterIterator($iterator, $this->visible);
        }

        return $iterator;
    }

    private function applyPublishedIterator(\Iterator $iterator): \Iterator
    {
        if (null !== $this->published) {
            $iterator = new Iterator\PublishedFilterIterator($iterator, $this->published);
        }

        return $iterator;
    }
}
