<?php

namespace ZeroGravity\Cms\Content;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Path\Path;

interface ReadablePage
{
    public function getContent(): ? string;

    /**
     * @return File[]
     */
    public function getFiles(): array;

    public function getFile(string $filename): ? File;

    public function getPath(): Path;

    public function getName(): string;

    public function getParent(): ? ReadablePage;

    public function getFilesystemPath(): Path;

    /**
     * Get all setting values.
     */
    public function getSettings(): array;

    /**
     * Get all non-default setting values. This will remove both OptionResolver defaults and child defaults of
     * the current parent page.
     */
    public function getNonDefaultSettings(): array;

    public function getChildren(): PageFinder;

    public function hasChildren(): bool;

    public function getSetting(string $name);

    public function getSlug(): string;

    /**
     * Check if this page has a custom slug that does not match its name.
     */
    public function hasCustomSlug(): bool;

    public function getTitle(): string;

    public function getContentType(): string;

    /**
     * Get all defined taxonomy keys and values.
     */
    public function getTaxonomies(): array;

    /**
     * Get values for a single taxonomy key.
     *
     * @param string $name
     */
    public function getTaxonomy($name): array;

    public function getTags(): array;

    public function getCategories(): array;

    public function getAuthors(): array;

    /**
     * Get default setting values for child pages.
     */
    public function getChildDefaults(): array;

    public function getExtraValues(): array;

    public function getMenuId(): string;

    public function getMenuLabel(): string;

    /**
     * Page is listed in menus.
     */
    public function isVisible(): bool;

    /**
     * Get custom template to embed this page in.
     */
    public function getLayoutTemplate(): ? string;

    /**
     * Get custom template for rendering the page content.
     */
    public function getContentTemplate(): ? string;

    /**
     * Get custom controller name to use for this page.
     */
    public function getController(): ? string;

    /**
     * @param mixed|null $default
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

    public function isPublished(): bool;
}
