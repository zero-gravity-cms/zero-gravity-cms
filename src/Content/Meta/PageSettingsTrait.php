<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * This trait contains settings related methods (mostly getters) of the Page class.
 * This helps to separate native properties from validated settings/options.
 */
trait PageSettingsTrait
{
    protected PageSettingsLoader $settingsLoader;

    public SettingValues $settings;

    abstract public function getParent(): ?ReadablePage;

    /**
     * @param array<string, mixed> $settings raw, unvalidated settings as passed to the OptionsResolver
     */
    protected function initSettings(array $settings, string $name): void
    {
        $this->settingsLoader = new PageSettingsLoader($settings, $name);
        $this->settings = $this->settingsLoader->values;
    }

    /**
     * Get all setting values.
     */
    public function getSettings(): SettingValues
    {
        return $this->settings;
    }

    public function getSlug(): string
    {
        return $this->settings->slug;
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
        return (string) $this->settings->title;
    }

    public function getContentType(): string
    {
        return $this->settings->content_type;
    }

    /**
     * Get all non-default setting values. This will remove both OptionResolver defaults and child defaults of
     * the current parent page.
     *
     * @return array<string, mixed>
     */
    public function getNonDefaultSettings(bool $serialize = false): array
    {
        return $this->settingsLoader->getNonDefaultValues($serialize, $this->getParent()?->getChildDefaults() ?? []);
    }

    /**
     * Get default setting values for child pages.
     *
     * @return array<string, mixed>
     */
    public function getChildDefaults(): array
    {
        return $this->settings->child_defaults;
    }

    /**
     * @return array<string, mixed>
     */
    public function getExtraValues(): array
    {
        return $this->settings->extra;
    }

    public function getMenuId(): string|bool
    {
        return $this->settings->menu_id;
    }

    public function getMenuLabel(): string
    {
        if (null !== $this->settings->menu_label && '' !== $this->settings->menu_label) {
            return $this->settings->menu_label;
        }

        return $this->getTitle();
    }

    /**
     * Page is listed in menus.
     */
    public function isVisible(): bool
    {
        return $this->settings->visible;
    }

    /**
     * Page is considered a modular page, not a regular content page.
     * Modular pages are designated to contain a collection of sub content or "module" pages.
     */
    public function isModular(): bool
    {
        return $this->settings->modular;
    }

    /**
     * Page is considered a snippet to be embedded in a "modular" page.
     * This is achieved automatically by prefixing the directory with an underscore.
     *
     * Module pages will be hidden from menus.
     */
    public function isModule(): bool
    {
        return $this->settings->module;
    }

    /**
     * Get custom template to embed this page's content in.
     */
    public function getLayoutTemplate(): ?string
    {
        return $this->settings->layout_template;
    }

    /**
     * Get custom template for rendering the page content.
     */
    public function getContentTemplate(): ?string
    {
        return $this->settings->content_template;
    }

    /**
     * Get custom controller name to use for this page.
     */
    public function getController(): ?string
    {
        return $this->settings->controller;
    }

    public function getExtra(string $name, mixed $default = null): mixed
    {
        $extra = $this->getExtraValues();
        if (array_key_exists($name, $extra)) {
            return $extra[$name];
        }

        return $default;
    }

    /**
     * Get optional date information of this page.
     */
    public function getDate(): ?DateTimeImmutable
    {
        return $this->settings->date;
    }
}
