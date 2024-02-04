<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Meta\PageSettings;
use ZeroGravity\Cms\Filesystem\Directory;

/**
 * @phpstan-import-type SettingValue from PageSettings
 */
interface WritablePage extends ReadablePage
{
    public function getDirectory(): ?Directory;

    /**
     * Set page name.
     */
    public function setName(string $name): void;

    public function setParent(ReadablePage $parent = null): void;

    /**
     * Get raw (un-processed) markdown content.
     */
    public function getContentRaw(): ?string;

    /**
     * Set raw (un-processed) markdown content.
     */
    public function setContentRaw(string $contentRaw = null): void;

    /**
     * Set page settings as plain array.
     *
     * @param array<string, SettingValue> $settings
     */
    public function setSettings(array $settings): void;
}
