<?php

namespace ZeroGravity\Cms\Content;

interface WritablePage extends ReadablePage
{
    /**
     * Set page name.
     *
     * @param string $name
     */
    public function setName(string $name): void;

    /**
     * @param ReadablePage|null $parent
     */
    public function setParent(ReadablePage $parent = null): void;

    /**
     * Get raw (un-processed) markdown content.
     *
     * @return string|null
     */
    public function getContentRaw(): ? string;

    /**
     * Set raw (un-processed) markdown content.
     *
     * @param string|null $contentRaw
     */
    public function setContentRaw(string $contentRaw = null): void;

    /**
     * Set page settings as plain array.
     *
     * @param array $settings
     */
    public function setSettings(array $settings): void;
}
