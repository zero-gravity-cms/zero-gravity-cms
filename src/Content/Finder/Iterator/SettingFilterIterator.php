<?php

namespace ZeroGravity\Cms\Content\Finder\Iterator;

use FilterIterator;
use Iterator;
use Traversable;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * SettingFilterIterator filters out pages that do not match the required setting value.
 *
 * @method ReadablePage current()
 *
 * @extends FilterIterator<string, ReadablePage, Traversable<string, ReadablePage>>
 */
final class SettingFilterIterator extends FilterIterator
{
    /**
     * @param Iterator<string, ReadablePage> $iterator The Iterator to filter
     * @param list<SettingFilter>            $settings
     */
    public function __construct(
        Iterator $iterator,
        private readonly array $settings,
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
        $page = $this->current();
        foreach ($this->settings as $settingFilter) {
            $valuesMatch = $settingFilter->value() === $page->getSetting($settingFilter->name());
            $isInverted = $settingFilter->isInverted();

            if ($isInverted === $valuesMatch) {
                return false;
            }
        }

        return true;
    }
}
