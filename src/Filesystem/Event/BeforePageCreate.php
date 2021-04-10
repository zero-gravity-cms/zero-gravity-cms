<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Directory;

final class BeforePageCreate extends Event
{
    private Directory $directory;

    private array $settings;

    private ?Page $parentPage;

    public function __construct(Directory $directory, array $settings, ?Page $parentPage = null)
    {
        $this->directory = $directory;
        $this->settings = $settings;
        $this->parentPage = $parentPage;
    }

    public function getDirectory(): Directory
    {
        return $this->directory;
    }

    public function getSettings(): array
    {
        return $this->settings;
    }

    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function getParentPage(): ?Page
    {
        return $this->parentPage;
    }
}
