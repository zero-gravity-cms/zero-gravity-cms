<?php

namespace ZeroGravity\Cms\Filesystem\Event;

class BeforePageSave extends PageDiffEvent
{
    public const BEFORE_PAGE_SAVE = 'zerogravity.filesystem.before_page_save';
}
