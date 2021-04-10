<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * This trait contains settings related methods (mostly getters) of the Page class.
 * This helps separating native properties from validated settings/options.
 */
trait PageSettingsTrait
{
    protected PageSettings $settings;

    abstract public function getParent(): ?ReadablePage;

    private function initSettings(array $settings, string $name)
    {
        $this->settings = new PageSettings($settings, $name);
    }

    public function getSetting(string $name)
    {
        return $this->settings->get($name);
    }

    public function getSlug(): string
    {
        return (string) $this->getSetting('slug');
    }

    /**
     * Check if this page has a custom slug that does not match its name.
     */
    public function hasCustomSlug(): bool
    {
        return array_key_exists('slug', $this->getNonDefaultSettings());
    }

    public function getTitle(): string
    {
        return (string) $this->getSetting('title');
    }

    public function getContentType(): string
    {
        return $this->getSetting('content_type');
    }

    public function getSettings(): array
    {
        return $this->settings->toArray();
    }

    /**
     * Get all non-default setting values. This will remove both OptionResolver defaults and child defaults of
     * the current parent page.
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
     */
    public function getChildDefaults(): array
    {
        return $this->getSetting('child_defaults');
    }

    public function getExtraValues(): array
    {
        return (array) $this->getSetting('extra');
    }

    public function getMenuId(): string
    {
        return (string) $this->getSetting('menu_id');
    }

    public function getMenuLabel(): string
    {
        if (!empty($this->getSetting('menu_label'))) {
            return (string) $this->getSetting('menu_label');
        }

        return $this->getTitle();
    }

    /**
     * Page is listed in menus.
     */
    public function isVisible(): bool
    {
        return (bool) $this->getSetting('visible');
    }

    /**
     * Page is considered a modular page, not a content page.
     */
    public function isModular(): bool
    {
        return (bool) $this->getSetting('modular');
    }

    /**
     * Page is considered a modular snippet, not a standalone page.
     */
    public function isModule(): bool
    {
        return (bool) $this->getSetting('module');
    }

    /**
     * Get custom template to embed this page in.
     */
    public function getLayoutTemplate(): ?string
    {
        return $this->getSetting('layout_template');
    }

    /**
     * Get custom template for rendering the page content.
     */
    public function getContentTemplate(): ?string
    {
        return $this->getSetting('content_template');
    }

    /**
     * Get custom controller name to use for this page.
     */
    public function getController(): ?string
    {
        return (string) $this->getSetting('controller');
    }

    /**
     * @param mixed|null $default
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
    public function getDate(): ?DateTimeImmutable
    {
        return $this->getSetting('date');
    }
}
