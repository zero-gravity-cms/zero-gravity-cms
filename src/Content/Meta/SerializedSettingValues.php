<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Meta;

use ArrayAccess;
use OutOfBoundsException;

/**
 * @phpstan-import-type TaxonomySettingValue from PageSettingsLoader
 *
 * @phpstan-type StoredSerializedSettingValue string|bool|array<string, mixed>|null
 *
 * @phpstan-implements ArrayAccess<string, StoredSerializedSettingValue>
 */
final readonly class SerializedSettingValues implements ArrayAccess
{
    public function __construct(
        /**
         * Serialized child-defaults map; the recursive edge, so values stay mixed.
         *
         * @var array<string, mixed>
         */
        public array $child_defaults,
        public ?string $content_template,
        public string $content_type,
        public ?string $controller,
        public ?string $date,
        /**
         * @var array<string, mixed>
         */
        public array $extra,
        /**
         * @var array<string, string>
         */
        public array $file_aliases,
        public ?string $layout_template,
        public string|false $menu_id,
        public ?string $menu_label,
        public bool $modular,
        public bool $module,
        public bool $publish,
        public ?string $publish_date,
        public string $slug,
        /**
         * @var array<string, TaxonomySettingValue>
         */
        public array $taxonomy,
        public ?string $title,
        public ?string $unpublish_date,
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
     * @return StoredSerializedSettingValue
     */
    public function offsetGet(mixed $offset): string|bool|array|null
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
     * Compare setting value with external value by name. Other value must be serialized too.
     *
     * @param PageSettingsLoader::KEY_* $name
     */
    public function valueMatches(string $name, mixed $otherValue): bool
    {
        return $this->offsetGet($name) === $otherValue;
    }

    /**
     * Get array copy of all settings.
     *
     * @return array{
     *      child_defaults: array<string, mixed>,
     *      content_template: string|null,
     *      content_type: string,
     *      controller: string|null,
     *      date: string|null,
     *      extra: array<string, mixed>,
     *      file_aliases: array<string, string>,
     *      layout_template: string|null,
     *      menu_id: string|false,
     *      menu_label: string|null,
     *      modular: bool,
     *      module: bool,
     *      publish: bool,
     *      publish_date: string|null,
     *      slug: string,
     *      taxonomy: array<string, list<string>>,
     *      title: string|null,
     *      unpublish_date: string|null,
     *      visible: bool
     * }
     */
    public function toArray(): array
    {
        return (array) $this;
    }
}
