<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ZeroGravity\Cms\Content\Page;

/**
 * ModularFilterIterator filters out pages that do not match the required modular state.
 *
 * @method Page current()
 */
class ModularFilterIterator extends \FilterIterator
{
    /**
     * @var bool
     */
    private $modular;

    /**
     * @param \Iterator $iterator The Iterator to filter
     * @param bool      $modular
     */
    public function __construct(\Iterator $iterator, bool $modular)
    {
        $this->modular = $modular;

        parent::__construct($iterator);
    }

    /**
     * Filters the iterator values.
     *
     * @return bool true if the value should be kept, false otherwise
     */
    public function accept()
    {
        return $this->current()->isModular() === $this->modular;
    }
}
