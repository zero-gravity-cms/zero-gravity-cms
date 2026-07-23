<?php

declare(strict_types=1);

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeInterface;
use InvalidArgumentException;

/**
 * Serializes SettingValues (a plain DTO) into their primitive SerializedSettingValues form.
 */
final class SettingValuesSerializer
{
    /**
     * Get a serialized copy of all settings as an object.
     */
    public static function serialize(SettingValues $values): SerializedSettingValues
    {
        return new SerializedSettingValues(
            child_defaults: self::serializeMap($values->child_defaults),
            content_template: $values->content_template,
            content_type: $values->content_type,
            controller: $values->controller,
            date: self::serializeDate($values->date),
            extra: self::serializeMap($values->extra),
            file_aliases: $values->file_aliases,
            layout_template: $values->layout_template,
            menu_id: $values->menu_id,
            menu_label: $values->menu_label,
            modular: $values->modular,
            module: $values->module,
            publish: $values->publish,
            publish_date: self::serializeDate($values->publish_date),
            slug: $values->slug,
            taxonomy: $values->taxonomy,
            title: $values->title,
            unpublish_date: self::serializeDate($values->unpublish_date),
            visible: $values->visible,
        );
    }

    /**
     * Recursively serialize an arbitrary setting value, converting dates to strings.
     *
     * This is the deliberately loose recursive edge: setting values can nest arbitrarily
     * (child_defaults is a settings-map of settings-maps), which PHPStan cannot express as a
     * recursive type alias, so both param and return are mixed. Precise types live on the
     * SettingValues / SerializedSettingValues properties instead.
     */
    public static function serializeValue(mixed $value): mixed
    {
        if (is_scalar($value) || null === $value) {
            return $value;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }
        if (is_array($value)) {
            return array_map(self::serializeValue(...), $value);
        }

        throw new InvalidArgumentException('Unknown value type: '.get_debug_type($value));
    }

    /**
     * Serialize a settings map (extra or child_defaults) recursively. Both hold arbitrary nested
     * data, i.e. the recursive edge, so values stay mixed.
     *
     * @param array<string, mixed> $value
     *
     * @return array<string, mixed>
     */
    private static function serializeMap(array $value): array
    {
        return array_map(self::serializeValue(...), $value);
    }

    /**
     * Serialize a date setting value to its string representation.
     */
    private static function serializeDate(?DateTimeInterface $value): ?string
    {
        return $value?->format('Y-m-d H:i:s');
    }
}
