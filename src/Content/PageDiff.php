<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Meta\PageSettings;

/**
 * @phpstan-import-type SettingValue from PageSettings
 * @phpstan-import-type SerializedSettingValue from PageSettings
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
        return $this->old->getSettings(true) !== $this->new->getSettings(true);
    }

    /**
     * @return array<string, SettingValue>
     * @return ($serialize is true ? array<string, SerializedSettingValue> : array<string, SettingValue>)
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
