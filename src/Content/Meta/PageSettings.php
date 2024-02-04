<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ZeroGravity\Cms\Content\Page;

/**
 * @phpstan-type TaxonomySettingValue array<string>
 * @phpstan-type DateTimeSettingValue int|string|DateTimeInterface
 * @phpstan-type SettingValue null|string|bool|DateTimeSettingValue|array<string, TaxonomySettingValue>|array<string, mixed>
 * @phpstan-type SerializedSettingValue null|string|bool|int|array<string, TaxonomySettingValue>|array<string, mixed>
 * @phpstan-type SettingValues array{
 *      child_defaults: null|array<string, SettingValue>,
 *      content_template: null|string,
 *      content_type: string,
 *      controller: string|null,
 *      date: null|string|int|DateTimeInterface,
 *      extra: array<string, mixed>,
 *      file_aliases: array<string, string>,
 *      layout_template: null|string,
 *      menu_id: string|false,
 *      menu_label: null|string,
 *      modular: bool,
 *      module: bool,
 *      publish: bool,
 *      publish_date: null|string|int|DateTimeInterface,
 *      slug: string,
 *      taxonomy: array<string, list<string>>,
 *      title: null|string,
 *      unpublish_date: null|string|int|DateTimeInterface,
 *      visible: bool
 * }
 * @phpstan-type SerializedSettingValues array{
 *      child_defaults: null|array<string, mixed>,
 *      content_template: null|string,
 *      content_type: string,
 *      controller: string|null,
 *      date: null|string,
 *      extra: array<string, mixed>,
 *      file_aliases: array<string, string>,
 *      layout_template: null|string,
 *      menu_id: string|false,
 *      menu_label: null|string,
 *      modular: bool,
 *      module: bool,
 *      publish: bool,
 *      publish_date: null|string,
 *      slug: string,
 *      taxonomy: array<string, list<string>>,
 *      title: null|string,
 *      unpublish_date: null|string,
 *      visible: bool
 * }
 */
final class PageSettings
{
    /**
     * @var SettingValues|null
     */
    private ?array $values = null;

    /**
     * @param array<string, SettingValue> $values
     */
    public function __construct(
        array $values,
        private readonly string $pageName,
    ) {
        $this->validate($values);
    }

    /**
     * Get a single setting value.
     */
    public function get(string $name): mixed
    {
        return $this->values[$name];
    }

    /**
     * Get array copy of all settings.
     *
     * @param bool $serialize set true to convert all object setting types (e.g. dates) to primitive values
     *
     * @return ($serialize is true ? SerializedSettingValues : SettingValues)
     */
    public function toArray(bool $serialize = false): array
    {
        return $serialize ? $this->serialize($this->values) : $this->values;
    }

    /**
     * Get all values that wouldn't have been set by default.
     *
     * @param bool $serialize set true to convert all object setting types (e.g. dates) to primitive values
     *
     * @return ($serialize is true ? array<string, SerializedSettingValue> : array<string, SettingValue>)
     */
    public function getNonDefaultValues(bool $serialize = false): array
    {
        $defaults = (new self([], $this->pageName))->toArray();

        $nonDefaults = [];
        foreach ($this->toArray($serialize) as $key => $value) {
            if (!array_key_exists($key, $defaults) || $defaults[$key] !== $value) {
                $nonDefaults[$key] = $value;
            }
        }

        return $nonDefaults;
    }

    /**
     * Resolve and validate page settings.
     * If everything was fine, assign them.
     *
     * @param array<string, SettingValue> $values
     */
    private function validate(array $values): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->values = $resolver->resolve($values);
        ksort($this->values);
    }

    /**
     * Configure validation rules for page settings.
     */
    private function configureOptions(OptionsResolver $resolver): void
    {
        $this->configureDefaults($resolver);
        $this->configureAllowedTypes($resolver);
        $this->configureNormalizers($resolver);
    }

    private function configureDefaults(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'child_defaults' => [],
            'content_template' => null,
            'content_type' => 'page',
            'controller' => null,
            'date' => null,
            'extra' => [],
            'file_aliases' => [],
            'layout_template' => null,
            'menu_id' => 'zero-gravity',
            'menu_label' => null,
            'modular' => false,
            'module' => false,
            'publish' => true,
            'publish_date' => null,
            'slug' => $this->pageName,
            'taxonomy' => [],
            'title' => null,
            'unpublish_date' => null,
            'visible' => false,
        ]);
    }

    private function configureAllowedTypes(OptionsResolver $resolver): void
    {
        $dateTypes = ['null', 'string', 'int', DateTimeInterface::class];

        $resolver->setAllowedTypes('child_defaults', ['null', 'array']);
        $resolver->setAllowedTypes('content_template', ['null', 'string']);
        $resolver->setAllowedTypes('content_type', 'string');
        $resolver->setAllowedTypes('controller', ['null', 'string']);
        $resolver->setAllowedTypes('date', $dateTypes);
        $resolver->setAllowedTypes('extra', ['null', 'array']);
        $resolver->setAllowedTypes('file_aliases', ['null', 'array']);
        $resolver->setAllowedTypes('layout_template', ['null', 'string']);
        $resolver->setAllowedTypes('menu_id', ['string', 'bool']);
        $resolver->setAllowedTypes('menu_label', ['null', 'string']);
        $resolver->setAllowedTypes('modular', 'bool');
        $resolver->setAllowedTypes('module', 'bool');
        $resolver->setAllowedTypes('publish_date', $dateTypes);
        $resolver->setAllowedTypes('taxonomy', ['null', 'array']);
        $resolver->setAllowedTypes('title', ['null', 'string']);
        $resolver->setAllowedTypes('unpublish_date', $dateTypes);
        $resolver->setAllowedTypes('visible', 'bool');
    }

    private function configureNormalizers(OptionsResolver $resolver): void
    {
        $this->normalizeDates($resolver);
        $this->normalizeTitle($resolver);
        $this->normalizeTaxonomy($resolver);
        $this->normalizeArrayValues($resolver);
    }

    private function normalizeDates(OptionsResolver $resolver): void
    {
        $normalizeDateTime = static function (Options $options, $value): ?DateTimeImmutable {
            if (null === $value) {
                return null;
            }
            if ($value instanceof DateTimeImmutable) {
                return $value;
            }
            if ($value instanceof DateTimeInterface) {
                $value = $value->format('c');
            } elseif (is_int($value)) {
                $value = '@'.$value;
            }

            return new DateTimeImmutable((string) $value);
        };
        $resolver->setNormalizer('date', $normalizeDateTime);
        $resolver->setNormalizer('publish_date', $normalizeDateTime);
        $resolver->setNormalizer('unpublish_date', $normalizeDateTime);
    }

    private function normalizeTitle(OptionsResolver $resolver): void
    {
        $normalizeTitle = function (Options $options, $value): string {
            if (null !== $value) {
                return (string) $value;
            }
            $name = $this->pageName;
            if (preg_match(Page::SORTING_PREFIX_PATTERN, $name, $matches)) {
                $name = $matches[1];
            }

            return trim(ucwords(str_replace(['-', '_'], ' ', $name)));
        };
        $resolver->setNormalizer('title', $normalizeTitle);
    }

    private function normalizeTaxonomy(OptionsResolver $resolver): void
    {
        $normalizeTaxonomy = static function (Options $options, $value): array {
            if (null === $value) {
                return [];
            }
            $taxonomies = [];
            foreach ($value as $name => $taxonomy) {
                $taxonomies[$name] = array_values((array) $taxonomy);
            }
            ksort($taxonomies);

            return $taxonomies;
        };
        $resolver->setNormalizer('taxonomy', $normalizeTaxonomy);
    }

    private function normalizeArrayValues(OptionsResolver $resolver): void
    {
        $normalizeArray = static function (Options $options, $value) {
            if (null === $value) {
                return [];
            }

            return $value;
        };
        $resolver->setNormalizer('extra', $normalizeArray);
        $resolver->setNormalizer('child_defaults', $normalizeArray);
        $resolver->setNormalizer('file_aliases', $normalizeArray);
    }

    private function serialize(mixed $value): mixed
    {
        if (is_scalar($value)) {
            return $value;
        }
        if (is_array($value)) {
            return array_map(fn ($singleValue): mixed => $this->serialize($singleValue), $value);
        }
        if (null === $value) {
            return null;
        }
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        return (string) $value;
    }
}
