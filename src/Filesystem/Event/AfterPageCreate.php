<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Component\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Page;

class AfterPageCreate extends Event
{
    public const AFTER_PAGE_CREATE = 'zerogravity.filesystem.after_page_create';

    /**
     * @var Page
     */
    private $page;

    public function __construct(Page $page)
    {
        $this->page = $page;
    }

    /**
     * @return Page
     */
    public function getPage(): Page
    {
        return $this->page;
    }
}
