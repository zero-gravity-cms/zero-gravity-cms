<?php

namespace ZeroGravity\Cms\Content;

interface WritablePage extends ReadablePage
{
    /**
     * Set page name.
     */
    public function setName(string $name): void;

    public function setParent(ReadablePage $parent = null): void;

    /**
     * Get raw (un-processed) markdown content.
     */
    public function getContentRaw(): ? string;

    /**
     * Set raw (un-processed) markdown content.
     */
    public function setContentRaw(string $contentRaw = null): void;

    /**
     * Set page settings as plain array.
     */
    public function setSettings(array $settings): void;
}
