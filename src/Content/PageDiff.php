<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Meta\PageSettingsLoader;
use ZeroGravity\Cms\Content\Meta\SettingValuesSerializer;

/**
 * @phpstan-import-type RawSettingValue from PageSettingsLoader
 * @phpstan-import-type SerializedSettingValue from PageSettingsLoader
 */
final readonly class PageDiff
{
    public function __construct(
        private WritablePage $old,
        private WritablePage $new,
    ) {
    }

    public function getOld(): WritablePage
    {
        return $this->old;
    }

    public function getNew(): WritablePage
    {
        return $this->new;
    }

    public function filesystemPathHasChanged(): bool
    {
        return $this->old->getFilesystemPath()->toString() !== $this->new->getFilesystemPath()->toString();
    }

    public function getNewFilesystemPath(): string
    {
        return $this->new->getFilesystemPath();
    }

    public function settingsHaveChanged(): bool
    {
        return SettingValuesSerializer::serialize($this->old->getSettings())->toArray() !== SettingValuesSerializer::serialize($this->new->getSettings())->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function getNewNonDefaultSettings(bool $serialize = false): array
    {
        return $this->new->getNonDefaultSettings($serialize);
    }

    public function contentHasChanged(): bool
    {
        return $this->old->getContentRaw() !== $this->new->getContentRaw();
    }

    public function getNewContentRaw(): string
    {
        return $this->new->getContentRaw();
    }

    public function containsInstancesOf(string $class): bool
    {
        return $this->old instanceof $class && $this->new instanceof $class;
    }
}
