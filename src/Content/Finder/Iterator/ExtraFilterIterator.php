<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use ZeroGravity\Cms\Content\Page;

/**
 * ExtraFilterIterator filters out pages that do not match the required extra setting value.
 *
 * @method Page current()
 */
class ExtraFilterIterator extends \FilterIterator
{
    /**
     * @var array
     */
    private $extras;

    /**
     * @var array
     */
    private $notExtras;

    /**
     * @param \Iterator $iterator  The Iterator to filter
     * @param array     $extras
     * @param array     $notExtras
     */
    public function __construct(\Iterator $iterator, array $extras, array $notExtras)
    {
        $this->extras = $extras;
        $this->notExtras = $notExtras;

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
        foreach ($this->extras as $extraSet) {
            $key = $extraSet[0];
            $value = $extraSet[1];
            if ($page->getExtraValue($key) != $value) {
                return false;
            }
        }

        foreach ($this->notExtras as $extraSet) {
            $key = $extraSet[0];
            $value = $extraSet[1];
            if ($page->getExtraValue($key) == $value) {
                return false;
            }
        }

        return true;
    }
}
