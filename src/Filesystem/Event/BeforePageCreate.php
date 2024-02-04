<?php

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Meta\PageSettings;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Directory;

/**
 * @phpstan-import-type SettingValue from PageSettings
 */
final class BeforePageCreate extends Event
{
    /**
     * @param array<string, SettingValue> $settings
     */
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

    /**
     * @return array<string, SettingValue>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array<string, SettingValue> $settings
     */
    public function setSettings(array $settings): void
    {
        $this->settings = $settings;
    }

    public function getParentPage(): ?Page
    {
        return $this->parentPage;
    }
}
