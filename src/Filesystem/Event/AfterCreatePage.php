<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Component\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Page;

class AfterCreatePage extends Event
{
    public const AFTER_CREATE_PAGE = 'zerogravity.filesystem.after_create_page';

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
