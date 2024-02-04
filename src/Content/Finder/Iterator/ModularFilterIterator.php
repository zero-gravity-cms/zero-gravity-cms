<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use Iterator;
use ZeroGravity\Cms\Content\Page;

/**
 * ModularFilterIterator filters out pages that do not match the required modular state.
 *
 * @method Page current()
 */
final class ModularFilterIterator extends FilterIterator
{
    /**
     * @param Iterator $iterator The Iterator to filter
     */
    public function __construct(
        Iterator $iterator,
        private readonly bool $modular,
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
        return $this->current()->isModular() === $this->modular;
    }
}
