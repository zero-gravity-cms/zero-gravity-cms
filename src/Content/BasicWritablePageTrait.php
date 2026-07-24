<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Filesystem\Directory;

trait BasicWritablePageTrait
{
    private string $contentRaw = '';

    private readonly ?Directory $directory;

    /**
     * Set page name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->buildPath();
        $this->buildFilesystemPath();
    }

    public function setParent(?ReadablePage $parent = null): void
    {
        $this->initParent($parent);
    }

    /**
     * Get raw (un-processed) markdown content.
     */
    public function getContentRaw(): string
    {
        return $this->contentRaw;
    }

    /**
     * Set raw (un-processed) markdown content.
     */
    public function setContentRaw(string $contentRaw = ''): void
    {
        $this->contentRaw = str_replace("\r\n", "\n", $contentRaw);
    }

    /**
     * Set page settings as plain array.
     *
     * @param array<string, mixed> $settings raw, unvalidated settings as passed to the OptionsResolver
     */
    public function setSettings(array $settings): void
    {
        $this->initSettings($settings, $this->name);
        $this->buildPath();
    }

    public function getDirectory(): ?Directory
    {
        return $this->directory;
    }
}
