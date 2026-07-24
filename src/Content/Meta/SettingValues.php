<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Meta;

use ArrayAccess;
use DateTimeImmutable;
use OutOfBoundsException;

/**
 * Plain data object holding the resolved, validated page settings.
 *
 * @phpstan-import-type TaxonomySettingValue from PageSettingsLoader
 *
 * @phpstan-type StoredSettingValue string|bool|DateTimeImmutable|array<string, mixed>|null
 * @phpstan-type SettingValuesAsArray array{
 *      child_defaults: array<string, mixed>,
 *      content_template: string|null,
 *      content_type: string,
 *      controller: string|null,
 *      date: DateTimeImmutable|null,
 *      extra: array<string, mixed>,
 *      file_aliases: array<string, string>,
 *      layout_template: string|null,
 *      menu_id: string|false,
 *      menu_label: string|null,
 *      modular: bool,
 *      module: bool,
 *      publish: bool,
 *      publish_date: DateTimeImmutable|null,
 *      slug: string,
 *      taxonomy: array<string, TaxonomySettingValue>,
 *      title: string|null,
 *      unpublish_date: DateTimeImmutable|null,
 *      visible: bool
 * }
 *
 * @phpstan-implements ArrayAccess<string, StoredSettingValue>
 */
final readonly class SettingValues implements ArrayAccess
{
    public function __construct(
        /**
         * @var array<string, mixed>
         */
        public array $child_defaults,
        public ?string $content_template,
        public string $content_type,
        public ?string $controller,
        public ?DateTimeImmutable $date,
        /**
         * @var array<string, mixed>
         */
        public array $extra,
        /**
         * Filename aliases, alias => existingFileName.
         *
         * @var array<string, string>
         */
        public array $file_aliases,
        public ?string $layout_template,
        public string|false $menu_id,
        public ?string $menu_label,
        public bool $modular,
        public bool $module,
        public bool $publish,
        public ?DateTimeImmutable $publish_date,
        public string $slug,
        /**
         * @var array<string, TaxonomySettingValue>
         */
        public array $taxonomy,
        public ?string $title,
        public ?DateTimeImmutable $unpublish_date,
        public bool $visible,
    ) {
    }

    /**
     * @param string|PageSettingsLoader::KEY_* $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return PageSettingsLoader::isValidKey($offset);
    }

    /**
     * @param string|PageSettingsLoader::KEY_* $offset
     *
     * @return StoredSettingValue
     */
    public function offsetGet(mixed $offset): string|bool|DateTimeImmutable|array|null
    {
        return match ($offset) {
            PageSettingsLoader::KEY_CHILD_DEFAULTS => $this->child_defaults,
            PageSettingsLoader::KEY_CONTENT_TEMPLATE => $this->content_template,
            PageSettingsLoader::KEY_CONTENT_TYPE => $this->content_type,
            PageSettingsLoader::KEY_CONTROLLER => $this->controller,
            PageSettingsLoader::KEY_DATE => $this->date,
            PageSettingsLoader::KEY_EXTRA => $this->extra,
            PageSettingsLoader::KEY_FILE_ALIASES => $this->file_aliases,
            PageSettingsLoader::KEY_LAYOUT_TEMPLATE => $this->layout_template,
            PageSettingsLoader::KEY_MENU_ID => $this->menu_id,
            PageSettingsLoader::KEY_MENU_LABEL => $this->menu_label,
            PageSettingsLoader::KEY_MODULAR => $this->modular,
            PageSettingsLoader::KEY_MODULE => $this->module,
            PageSettingsLoader::KEY_PUBLISH => $this->publish,
            PageSettingsLoader::KEY_PUBLISH_DATE => $this->publish_date,
            PageSettingsLoader::KEY_SLUG => $this->slug,
            PageSettingsLoader::KEY_TAXONOMY => $this->taxonomy,
            PageSettingsLoader::KEY_TITLE => $this->title,
            PageSettingsLoader::KEY_UNPUBLISH_DATE => $this->unpublish_date,
            PageSettingsLoader::KEY_VISIBLE => $this->visible,
            default => throw new OutOfBoundsException(),
        };
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new OutOfBoundsException();
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new OutOfBoundsException();
    }

    /**
     * Compare a named setting value with an external value using serialized comparison.
     * This filters object identities (e.g. dates) so that equal values compare as equal.
     *
     * @param PageSettingsLoader::KEY_* $name
     */
    public function valueMatches(string $name, mixed $otherValue): bool
    {
        return SettingValuesSerializer::serializeValue($this->offsetGet($name)) === SettingValuesSerializer::serializeValue($otherValue);
    }

    /**
     * Get array copy of all settings.
     *
     * @return SettingValuesAsArray
     */
    public function toArray(): array
    {
        return (array) $this;
    }
}
