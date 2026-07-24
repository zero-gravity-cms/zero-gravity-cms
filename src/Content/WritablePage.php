<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Filesystem\Directory;

interface WritablePage extends ReadablePage
{
    public function getDirectory(): ?Directory;

    /**
     * Set page name.
     */
    public function setName(string $name): void;

    public function setParent(?ReadablePage $parent = null): void;

    /**
     * Get raw (un-processed) markdown content.
     */
    public function getContentRaw(): string;

    /**
     * Set raw (un-processed) markdown content.
     */
    public function setContentRaw(string $contentRaw = ''): void;

    /**
     * Set page settings as plain array.
     *
     * @param array<string, mixed> $settings raw, unvalidated settings as passed to the OptionsResolver
     */
    public function setSettings(array $settings): void;
}
