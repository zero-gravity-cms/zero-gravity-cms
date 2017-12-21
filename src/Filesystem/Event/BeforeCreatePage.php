<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Component\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Directory;

class BeforeCreatePage extends Event
{
    public const BEFORE_CREATE_PAGE = 'zerogravity.filesystem.before_create_page';

    /**
     * @var Directory
     */
    private $directory;

    /**
     * @var array
     */
    private $settings;

    /**
     * @var Page|null
     */
    private $parentPage;

    public function __construct(Directory $directory, array $settings, Page $parentPage = null)
    {
        $this->directory = $directory;
        $this->settings = $settings;
        $this->parentPage = $parentPage;
    }

    /**
     * @return Directory
     */
    public function getDirectory(): Directory
    {
        return $this->directory;
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    /**
     * @return null|Page
     */
    public function getParentPage(): ?Page
    {
        return $this->parentPage;
    }
}
