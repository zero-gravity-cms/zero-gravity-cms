<?php

namespace ZeroGravity\Cms\Content;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Meta\PageSettings;
use ZeroGravity\Cms\Path\Path;

/**
 * This trait contains settings related methods (mostly getters) of the Page class.
 * This helps to separate native properties from validated settings/options.
 *
 * @phpstan-import-type SettingValue from PageSettings
 * @phpstan-import-type SettingValues from PageSettings
 * @phpstan-import-type SerializedSettingValue from PageSettings
 * @phpstan-import-type SerializedSettingValues from PageSettings
 */
interface ReadablePage
{
    public function getContent(): ?string;

    /**
     * @return array<string, File>
     */
    public function getFiles(): array;

    public function getFile(string $filename): ?File;

    /**
     * Get representations for all available image files.
     *
     * @return array<string, File>
     */
    public function getImages(): array;

    /**
     * Get representations for all available document files.
     *
     * @return array<string, File>
     */
    public function getDocuments(): array;

    /**
     * Get single markdown file representation if available.
     */
    public function getMarkdownFile(): ?File;

    /**
     * Get single YAML file representation if available.
     */
    public function getYamlFile(): ?File;

    /**
     * Get single Twig file representation if available.
     */
    public function getTwigFile(): ?File;

    public function getPath(): Path;

    public function getName(): string;

    public function getParent(): ?ReadablePage;

    public function getFilesystemPath(): Path;

    /**
     * Get all setting values.
     *
     * @param bool $serialize set true to convert all object setting types (e.g. dates) to primitive values
     *
     * @return ($serialize is true ? SerializedSettingValues : SettingValues)
     */
    public function getSettings(bool $serialize = false): array;

    /**
     * Get all non-default setting values. This will remove both OptionResolver defaults and child defaults of
     * the current parent page.
     *
     * @param bool $serialize set true to convert all object setting types (e.g. dates) to primitive values
     *
     * @return ($serialize is true ? array<string, SerializedSettingValue> : array<string, SettingValue>)
     */
    public function getNonDefaultSettings(bool $serialize = false): array;

    public function getChildren(): PageFinder;

    public function hasChildren(): bool;

    public function getSetting(string $name): mixed;

    public function getSlug(): string;

    /**
     * Check if this page has a custom slug that does not match its name.
     */
    public function hasCustomSlug(): bool;

    public function getTitle(): string;

    public function getContentType(): string;

    /**
     * Get all defined taxonomy keys and values.
     *
     * @return array<string, list<string>>
     */
    public function getTaxonomies(): array;

    /**
     * Get values for a single taxonomy key.
     *
     * @return list<string>
     */
    public function getTaxonomy(string $name): array;

    /**
     * @return list<string>
     */
    public function getTags(): array;

    /**
     * @return list<string>
     */
    public function getCategories(): array;

    /**
     * @return list<string>
     */
    public function getAuthors(): array;

    /**
     * Get default setting values for child pages.
     *
     * @return array<string, SettingValue>
     */
    public function getChildDefaults(): array;

    /**
     * @return array<string, mixed>
     */
    public function getExtraValues(): array;

    public function getMenuId(): string|bool;

    public function getMenuLabel(): string;

    /**
     * Page is listed in menus.
     */
    public function isVisible(): bool;

    /**
     * Page is considered a modular page, not a regular content page.
     * Modular pages are designated to contain a collection of sub content or "module" pages.
     */
    public function isModular(): bool;

    /**
     * Page is considered a snippet to be embedded in a "modular" page.
     * This is achieved automatically by prefixing the directory with an underscore.
     *
     * Module pages will be hidden from menus.
     */
    public function isModule(): bool;

    /**
     * Get custom template to embed this page's content in.
     */
    public function getLayoutTemplate(): ?string;

    /**
     * Get custom template for rendering the page content.
     */
    public function getContentTemplate(): ?string;

    /**
     * Get custom controller name to use for this page.
     */
    public function getController(): ?string;

    public function getExtra(string $name, mixed $default = null): mixed;

    /**
     * Get optional date information of this page.
     */
    public function getDate(): ?DateTimeImmutable;

    /**
     * Get optional publishing date of this page.
     */
    public function getPublishDate(): ?DateTimeImmutable;

    /**
     * Get optional un-publishing date of this page.
     */
    public function getUnpublishDate(): ?DateTimeImmutable;

    /**
     * A page is considered published if the following criteria are met:
     *
     * - 'publish' flag is true (default)
     * - 'publish_date' is either null or in the past
     * - 'unpublish_date' is either null or in the future
     *
     * The parent's publishing state is not taken into account!
     */
    public function isPublished(): bool;
}
