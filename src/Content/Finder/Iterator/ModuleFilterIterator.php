<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ZeroGravity\Cms\Content\Page;

/**
 * ModuleFilterIterator filters out pages that do not match the required module state.
 *
 * @method Page current()
 */
class ModuleFilterIterator extends \FilterIterator
{
    /**
     * @var bool
     */
    private $module;

    /**
     * @param \Iterator $iterator The Iterator to filter
     */
    public function __construct(\Iterator $iterator, bool $module)
    {
        $this->module = $module;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->current()->isModule() === $this->module;
    }
}
