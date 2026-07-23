<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Filesystem\Event;

use Symfony\Contracts\EventDispatcher\Event;
use ZeroGravity\Cms\Content\Meta\PageSettingsLoader;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Directory;

/**
 * @phpstan-import-type RawSettingValue from PageSettingsLoader
 */
final class BeforePageCreate extends Event
{
    /**
     * @param array<string, RawSettingValue> $settings
     */
    public function __construct(
        private readonly Directory $directory,
        /**
         * @var array<string, mixed>
         */
        private array $settings,
        private readonly ?Page $parentPage = null,
    ) {
    }

    public function getDirectory(): Directory
    {
        return $this->directory;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSettings(): array
    {
        return $this->settings;
    }

    /**
     * @param array<string, mixed> $settings
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
