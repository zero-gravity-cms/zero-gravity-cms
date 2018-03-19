<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * This trait contains all settings related methods (mostly getters) of the Page class.
 * This helps separating native properties from validated settings/options.
 *
 * @method ReadablePage getParent()
 */
trait PageSettingsTrait
{
    /**
     * @var PageSettings
     */
    protected $settings;

    /**
     * @param array  $settings
     * @param string $name
     */
    private function initSettings(array $settings, string $name)
    {
        $this->settings = new PageSettings($settings, $name);
    }

    /**
     * @param string $name
     *
     * @return mixed
     */
    public function getSetting(string $name)
    {
        return $this->settings->get($name);
    }

    /**
     * @return string
     */
    public function getSlug(): string
    {
        return (string) $this->getSetting('slug');
    }

    /**
     * @return string
     */
    public function getTitle(): string
    {
        return (string) $this->getSetting('title');
    }

    /**
     * @return string
     */
    public function getContentType(): string
    {
        return $this->getSetting('content_type');
    }

    /**
     * Get all defined taxonomy keys and values.
     *
     * @return array
     */
    public function getTaxonomies(): array
    {
        return $this->getSetting('taxonomy');
    }

    /**
     * Get values for a single taxonomy key.
     *
     * @param string $name
     *
     * @return array
     */
    public function getTaxonomy($name): array
    {
        $taxonomy = $this->getSetting('taxonomy');
        if (isset($taxonomy[$name])) {
            return (array) $taxonomy[$name];
        }

        return [];
    }

    /**
     * @return array
     */
    public function getTags(): array
    {
        return $this->getTaxonomy(Page::TAXONOMY_TAG);
    }

    /**
     * @return array
     */
    public function getCategories(): array
    {
        return $this->getTaxonomy(Page::TAXONOMY_CATEGORY);
    }

    /**
     * @return array
     */
    public function getAuthors(): array
    {
        return $this->getTaxonomy(Page::TAXONOMY_AUTHOR);
    }

    /**
     * @return array
     */
    public function getSettings(): array
    {
        return $this->settings->toArray();
    }

    /**
     * Get all non-default setting values. This will remove both OptionResolver defaults and child defaults of
     * the current parent page.
     *
     * @return array
     */
    public function getNonDefaultSettings(): array
    {
        $settings = $this->settings->getNonDefaultValues();
        if (null === $this->getParent()) {
            return $settings;
        }

        foreach ($this->getParent()->getChildDefaults() as $key => $defaultValue) {
            if (array_key_exists($key, $settings) && $settings[$key] === $defaultValue) {
                unset($settings[$key]);
            }
        }

        return $settings;
    }

    /**
     * Get default setting values for child pages.
     *
     * @return array
     */
    public function getChildDefaults(): array
    {
        return $this->getSetting('child_defaults');
    }

    /**
     * @return array
     */
    public function getExtraValues(): array
    {
        return (array) $this->getSetting('extra');
    }

    /**
     * @return string
     */
    public function getMenuId(): string
    {
        return (string) $this->getSetting('menu_id');
    }

    /**
     * @return string
     */
    public function getMenuLabel(): string
    {
        if (!empty($this->getSetting('menu_label'))) {
            return (string) $this->getSetting('menu_label');
        }

        return $this->getTitle();
    }

    /**
     * Page is listed in menus.
     *
     * @return bool
     */
    public function isVisible(): bool
    {
        return (bool) $this->getSetting('visible');
    }

    /**
     * Page is considered a modular page, not a content page.
     *
     * @return bool
     */
    public function isModular(): bool
    {
        return (bool) $this->getSetting('modular');
    }

    /**
     * Page is considered a modular snippet, not a standalone page.
     *
     * @return bool
     */
    public function isModule(): bool
    {
        return (bool) $this->getSetting('module');
    }

    /**
     * Get custom template to embed this page in.
     *
     * @return string|null
     */
    public function getLayoutTemplate(): ? string
    {
        return $this->getSetting('layout_template');
    }

    /**
     * Get custom template for rendering the page content.
     *
     * @return string|null
     */
    public function getContentTemplate(): ? string
    {
        return $this->getSetting('content_template');
    }

    /**
     * Get custom controller name to use for this page.
     *
     * @return string|null
     */
    public function getController(): ? string
    {
        return (string) $this->getSetting('controller');
    }

    /**
     * @param string     $name
     * @param mixed|null $default
     *
     * @return mixed
     */
    public function getExtra(string $name, $default = null)
    {
        $extra = $this->getExtraValues();
        if (array_key_exists($name, $extra)) {
            return $extra[$name];
        }

        return $default;
    }

    /**
     * Get optional date information of this page.
     *
     * @return DateTimeImmutable
     */
    public function getDate(): ? DateTimeImmutable
    {
        return $this->getSetting('date');
    }

    /**
     * Get optional publishing date of this page.
     *
     * @return DateTimeImmutable
     */
    public function getPublishDate(): ? DateTimeImmutable
    {
        return $this->getSetting('publish_date');
    }

    /**
     * Get optional un-publishing date of this page.
     *
     * @return DateTimeImmutable
     */
    public function getUnpublishDate(): ? DateTimeImmutable
    {
        return $this->getSetting('unpublish_date');
    }

    /**
     * @return bool
     */
    public function isPublished(): bool
    {
        if (!$this->getSetting('publish')) {
            return false;
        }

        return $this->isCurrentDateWithinPublishDates();
    }

    /**
     * @return bool
     */
    public function isCurrentDateWithinPublishDates(): bool
    {
        if ($this->publishDateIsInFuture()) {
            return false;
        }
        if ($this->unpublishDateIsInPast()) {
            return false;
        }

        return true;
    }

    /**
     * @return bool
     */
    private function publishDateIsInFuture(): bool
    {
        if (null === $this->getPublishDate()) {
            return false;
        }
        if ($this->getPublishDate()->format('U') > time()) {
            return true;
        }

        return false;
    }

    /**
     * @return bool
     */
    private function unpublishDateIsInPast(): bool
    {
        if (null === $this->getUnpublishDate()) {
            return false;
        }
        if ($this->getUnpublishDate()->format('U') < time()) {
            return true;
        }

        return false;
    }
}
