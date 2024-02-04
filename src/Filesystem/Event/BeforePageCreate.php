<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Directory;

final class BeforePageCreate extends Event
{
    public function __construct(
        private readonly Directory $directory,
        private array $settings,
        private readonly ?Page $parentPage = null
    ) {
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
