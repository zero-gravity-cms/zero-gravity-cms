<?php

namespace ZeroGravity\Cms\Content;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Path\Path;

interface ReadablePage
{
    /**
     * @return string|null
     */
    public function getContent(): ? string;

    /**
     * @return File[]
     */
    public function getFiles(): array;

    /**
     * @param string $filename
     *
     * @return null|File
     */
    public function getFile(string $filename): ? File;

    /**
     * @return Path
     */
    public function getPath(): Path;

    /**
     * @return string
     */
    public function getName(): string;

    /**
     * @return self|null
     */
    public function getParent(): ? self;

    /**
     * @return Path
     */
    public function getFilesystemPath(): Path;

    /**
     * @return array
     */
    public function getSettings(): array;

    /**
     * @return PageFinder
     */
    public function getChildren(): PageFinder;

    /**
     * @return bool
     */
    public function hasChildren(): bool;

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getSetting(string $name);

    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @return string
     */
    public function getContentType(): string;

    /**
     * Get all defined taxonomy keys and values.
     *
     * @return array
     */
    public function getTaxonomies(): array;

    /**
     * Get values for a single taxonomy key.
     *
     * @param string $name
     *
     * @return array
     */
    public function getTaxonomy($name): array;

    /**
     * @return array
     */
    public function getTags(): array;

    /**
     * @return array
     */
    public function getCategories(): array;

    /**
     * @return array
     */
    public function getAuthors(): array;

    /**
     * Get default setting values for child pages.
     *
     * @return array
     */
    public function getChildDefaults(): array;

    /**
     * @return array
     */
    public function getExtraValues(): array;

    /**
     * @return string
     */
    public function getMenuId(): string;

    /**
     * @return string
     */
    public function getMenuLabel(): string;

    /**
     * Page is listed in menus.
     *
     * @return bool
     */
    public function isVisible(): bool;

    /**
     * Get custom template to embed this page in.
     *
     * @return string|null
     */
    public function getLayoutTemplate(): ? string;

    /**
     * Get custom template for rendering the page content.
     *
     * @return string|null
     */
    public function getContentTemplate(): ? string;

    /**
     * Get custom controller name to use for this page.
     *
     * @return string|null
     */
    public function getController(): ? string;

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getExtra(string $name, $default = null);

    /**
     * Get optional date information of this page.
     *
     * @return DateTimeImmutable
     */
    public function getDate(): ? DateTimeImmutable;

    /**
     * Get optional publishing date of this page.
     *
     * @return DateTimeImmutable
     */
    public function getPublishDate(): ? DateTimeImmutable;

    /**
     * Get optional un-publishing date of this page.
     *
     * @return DateTimeImmutable
     */
    public function getUnpublishDate(): ? DateTimeImmutable;

    /**
     * @return bool
     */
    public function isPublished(): bool;
}
