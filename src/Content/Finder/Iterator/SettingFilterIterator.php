<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use Iterator;
use ZeroGravity\Cms\Content\Page;

/**
 * SettingFilterIterator filters out pages that do not match the required setting value.
 *
 * @method Page current()
 */
class SettingFilterIterator extends FilterIterator
{
    /**
     * @var array
     */
    private $settings;

    /**
     * @var array
     */
    private $notSettings;

    /**
     * @param Iterator $iterator The Iterator to filter
     */
    public function __construct(Iterator $iterator, array $settings, array $notSettings)
    {
        $this->settings = $settings;
        $this->notSettings = $notSettings;

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
        foreach ($this->settings as $settingSet) {
            $key = $settingSet[0];
            $value = $settingSet[1];
            if ($page->getSetting($key) != $value) {
                return false;
            }
        }

        foreach ($this->notSettings as $settingSet) {
            $key = $settingSet[0];
            $value = $settingSet[1];
            if ($page->getSetting($key) == $value) {
                return false;
            }
        }

        return true;
    }
}
