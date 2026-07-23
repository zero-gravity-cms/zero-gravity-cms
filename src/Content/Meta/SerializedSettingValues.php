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
     * @param PageSettingsLoader::KEY_* $offset
     */
    public function offsetExists(mixed $offset): bool
    {
        return property_exists(self::class, $offset);
    }

    /**
     * @param PageSettingsLoader::KEY_* $offset
     *
     * @return StoredSerializedSettingValue
     */
    public function offsetGet(mixed $offset): string|bool|array|null
    {
        if (!property_exists(self::class, $offset)) {
            throw new OutOfBoundsException();
        }

        return $this->$offset;
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
        if (!property_exists($this, $name)) {
            throw new OutOfBoundsException();
        }

        return $this->$name === $otherValue;
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
