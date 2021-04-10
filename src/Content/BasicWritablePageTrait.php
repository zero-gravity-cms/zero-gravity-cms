<?php

namespace ZeroGravity\Cms\Content;

use ZeroGravity\Cms\Content\Meta\PageSettings;

trait BasicWritablePageTrait
{
    private ?string $contentRaw = null;

    /**
     * Set page name.
     */
    public function setName(string $name): void
    {
        $this->name = $name;
        $this->buildPath();
        $this->buildFilesystemPath();
    }

    public function setParent(ReadablePage $parent = null): void
    {
        $this->initParent($parent);
    }

    /**
     * Get raw (un-processed) markdown content.
     */
    public function getContentRaw(): ? string
    {
        return $this->contentRaw;
    }

    /**
     * Set raw (un-processed) markdown content.
     */
    public function setContentRaw(string $contentRaw = null): void
    {
        $this->contentRaw = str_replace("\r\n", "\n", $contentRaw);
    }

    /**
     * Set page settings as plain array.
     */
    public function setSettings(array $settings): void
    {
        $this->settings = new PageSettings($settings, $this->getName());
        $this->buildPath();
    }
}
