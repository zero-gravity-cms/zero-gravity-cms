<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Webmozart\Assert\Assert;
use Webmozart\Assert\InvalidArgumentException;
use ZeroGravity\Cms\Content\Page;

/**
 * @phpstan-type TaxonomySettingValue list<string>
 * @phpstan-type RawNestedSettingValue null|int|string|bool|DateTimeInterface
 * @phpstan-type SerializedNestedSettingValue null|int|string|bool
 * @phpstan-type RawSettingValue RawNestedSettingValue|array<string, TaxonomySettingValue>|array<string, RawNestedSettingValue>
 * @phpstan-type SerializedSettingValue SerializedNestedSettingValue|array<string, TaxonomySettingValue>|array<string, SerializedNestedSettingValue>
 * @phpstan-type RawSettingValues array{
 *      child_defaults: null|array<string, mixed>,
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
 */
final class PageSettingsLoader
{
    public const KEY_CHILD_DEFAULTS = 'child_defaults';

    public const KEY_CONTENT_TEMPLATE = 'content_template';

    public const KEY_CONTENT_TYPE = 'content_type';

    public const KEY_CONTROLLER = 'controller';

    public const KEY_DATE = 'date';

    public const KEY_EXTRA = 'extra';

    public const KEY_FILE_ALIASES = 'file_aliases';

    public const KEY_LAYOUT_TEMPLATE = 'layout_template';

    public const KEY_MENU_ID = 'menu_id';

    public const KEY_MENU_LABEL = 'menu_label';

    public const KEY_MODULAR = 'modular';

    public const KEY_MODULE = 'module';

    public const KEY_PUBLISH = 'publish';

    public const KEY_PUBLISH_DATE = 'publish_date';

    public const KEY_SLUG = 'slug';

    public const KEY_TAXONOMY = 'taxonomy';

    public const KEY_TITLE = 'title';

    public const KEY_UNPUBLISH_DATE = 'unpublish_date';

    public const KEY_VISIBLE = 'visible';

    public const VALID_KEYS = [
        self::KEY_CHILD_DEFAULTS => self::KEY_CHILD_DEFAULTS,
        self::KEY_CONTENT_TEMPLATE => self::KEY_CONTENT_TEMPLATE,
        self::KEY_CONTENT_TYPE => self::KEY_CONTENT_TYPE,
        self::KEY_CONTROLLER => self::KEY_CONTROLLER,
        self::KEY_DATE => self::KEY_DATE,
        self::KEY_EXTRA => self::KEY_EXTRA,
        self::KEY_FILE_ALIASES => self::KEY_FILE_ALIASES,
        self::KEY_LAYOUT_TEMPLATE => self::KEY_LAYOUT_TEMPLATE,
        self::KEY_MENU_ID => self::KEY_MENU_ID,
        self::KEY_MENU_LABEL => self::KEY_MENU_LABEL,
        self::KEY_MODULAR => self::KEY_MODULAR,
        self::KEY_MODULE => self::KEY_MODULE,
        self::KEY_PUBLISH => self::KEY_PUBLISH,
        self::KEY_PUBLISH_DATE => self::KEY_PUBLISH_DATE,
        self::KEY_SLUG => self::KEY_SLUG,
        self::KEY_TAXONOMY => self::KEY_TAXONOMY,
        self::KEY_TITLE => self::KEY_TITLE,
        self::KEY_UNPUBLISH_DATE => self::KEY_UNPUBLISH_DATE,
        self::KEY_VISIBLE => self::KEY_VISIBLE,
    ];

    public static function isValidKey(string $key): bool
    {
        return isset(self::VALID_KEYS[$key]);
    }

    public SettingValues $values;

    /**
     * @param array<string, mixed> $values raw, unvalidated settings as passed to the OptionsResolver
     */
    public function __construct(
        array $values,
        private readonly string $pageName,
    ) {
        $this->validate($values);
    }

    /**
     * Get all values that wouldn't have been set by default.
     *
     * @param bool                 $serialize             set true to convert all object setting types (e.g. dates) to primitive values
     * @param array<string, mixed> $excludeMatchingValues
     *
     * @return array<string, mixed>
     */
    public function getNonDefaultValues(bool $serialize = false, array $excludeMatchingValues = []): array
    {
        $defaultValues = (new self([], $this->pageName))->values;

        $nonDefaults = [];
        foreach (self::VALID_KEYS as $key) {
            if ($this->values->valueMatches($key, $defaultValues[$key])) {
                continue;
            }
            if (array_key_exists($key, $excludeMatchingValues) && $this->values->valueMatches($key, $excludeMatchingValues[$key])) {
                continue;
            }
            $nonDefaults[$key] = $this->values[$key];
        }
        if ($serialize) {
            return array_map(SettingValuesSerializer::serializeValue(...), $nonDefaults);
        }

        return $nonDefaults;
    }

    /**
     * Resolve and validate page settings.
     * If everything was fine, assign them.
     *
     * @param array<string, mixed> $rawValues raw, unvalidated settings as passed to the OptionsResolver
     */
    private function validate(array $rawValues): void
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $resolvedValues = $resolver->resolve($rawValues);
        $this->values = new SettingValues(
            child_defaults: $resolvedValues[self::KEY_CHILD_DEFAULTS],
            content_template: $resolvedValues[self::KEY_CONTENT_TEMPLATE],
            content_type: $resolvedValues[self::KEY_CONTENT_TYPE],
            controller: $resolvedValues[self::KEY_CONTROLLER],
            date: $resolvedValues[self::KEY_DATE],
            extra: $resolvedValues[self::KEY_EXTRA],
            file_aliases: $resolvedValues[self::KEY_FILE_ALIASES],
            layout_template: $resolvedValues[self::KEY_LAYOUT_TEMPLATE],
            menu_id: $resolvedValues[self::KEY_MENU_ID],
            menu_label: $resolvedValues[self::KEY_MENU_LABEL],
            modular: $resolvedValues[self::KEY_MODULAR],
            module: $resolvedValues[self::KEY_MODULE],
            publish: $resolvedValues[self::KEY_PUBLISH],
            publish_date: $resolvedValues[self::KEY_PUBLISH_DATE],
            slug: $resolvedValues[self::KEY_SLUG],
            taxonomy: $resolvedValues[self::KEY_TAXONOMY],
            title: $resolvedValues[self::KEY_TITLE],
            unpublish_date: $resolvedValues[self::KEY_UNPUBLISH_DATE],
            visible: $resolvedValues[self::KEY_VISIBLE],
        );
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
            self::KEY_CHILD_DEFAULTS => [],
            self::KEY_CONTENT_TEMPLATE => null,
            self::KEY_CONTENT_TYPE => 'page',
            self::KEY_CONTROLLER => null,
            self::KEY_DATE => null,
            self::KEY_EXTRA => [],
            self::KEY_FILE_ALIASES => [],
            self::KEY_LAYOUT_TEMPLATE => null,
            self::KEY_MENU_ID => 'zero-gravity',
            self::KEY_MENU_LABEL => null,
            self::KEY_MODULAR => false,
            self::KEY_MODULE => false,
            self::KEY_PUBLISH => true,
            self::KEY_PUBLISH_DATE => null,
            self::KEY_SLUG => $this->pageName,
            self::KEY_TAXONOMY => [],
            self::KEY_TITLE => null,
            self::KEY_UNPUBLISH_DATE => null,
            self::KEY_VISIBLE => false,
        ]);
    }

    private function configureAllowedTypes(OptionsResolver $resolver): void
    {
        $dateTypes = ['null', 'string', 'int', DateTimeInterface::class];

        $resolver->setAllowedTypes(self::KEY_CHILD_DEFAULTS, ['null', 'array']);
        $resolver->setAllowedTypes(self::KEY_CONTENT_TEMPLATE, ['null', 'string']);
        $resolver->setAllowedTypes(self::KEY_CONTENT_TYPE, 'string');
        $resolver->setAllowedTypes(self::KEY_CONTROLLER, ['null', 'string']);
        $resolver->setAllowedTypes(self::KEY_DATE, $dateTypes);
        $resolver->setAllowedTypes(self::KEY_EXTRA, ['null', 'array']);
        $resolver->setAllowedTypes(self::KEY_FILE_ALIASES, ['null', 'string[]']);
        $resolver->setAllowedTypes(self::KEY_LAYOUT_TEMPLATE, ['null', 'string']);
        $resolver->setAllowedTypes(self::KEY_MENU_ID, ['string', 'bool']);
        $resolver->setAllowedTypes(self::KEY_MENU_LABEL, ['null', 'string']);
        $resolver->setAllowedTypes(self::KEY_MODULAR, 'bool');
        $resolver->setAllowedTypes(self::KEY_MODULE, 'bool');
        $resolver->setAllowedTypes(self::KEY_PUBLISH_DATE, $dateTypes);
        $resolver->setAllowedTypes(self::KEY_TAXONOMY, ['null', 'array']);
        $resolver->setAllowedTypes(self::KEY_TITLE, ['null', 'string']);
        $resolver->setAllowedTypes(self::KEY_UNPUBLISH_DATE, $dateTypes);
        $resolver->setAllowedTypes(self::KEY_VISIBLE, 'bool');
    }

    private function configureNormalizers(OptionsResolver $resolver): void
    {
        $this->normalizeDates($resolver);
        $this->normalizeTitle($resolver);
        $this->normalizeTaxonomy($resolver);
        $this->normalizeExtraValues($resolver);
        $this->normalizeChildDefaults($resolver);
        $this->normalizeFileAliases($resolver);
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
        $resolver->setNormalizer(self::KEY_DATE, $normalizeDateTime);
        $resolver->setNormalizer(self::KEY_PUBLISH_DATE, $normalizeDateTime);
        $resolver->setNormalizer(self::KEY_UNPUBLISH_DATE, $normalizeDateTime);
    }

    private function normalizeTitle(OptionsResolver $resolver): void
    {
        $normalizeTitle = function (Options $options, $value): string {
            if (null !== $value) {
                return (string) $value;
            }
            $name = $this->pageName;
            if (1 === preg_match(Page::SORTING_PREFIX_PATTERN, $name, $matches)) {
                $name = $matches[1];
            }

            return trim(ucwords(str_replace(['-', '_'], ' ', $name)));
        };
        $resolver->setNormalizer(self::KEY_TITLE, $normalizeTitle);
    }

    private function normalizeTaxonomy(OptionsResolver $resolver): void
    {
        /**
         * @return array<string, list<string>>
         */
        $normalizeTaxonomy = static function (Options $options, mixed $value): array {
            if (null === $value) {
                return [];
            }
            if (!is_array($value)) {
                throw new InvalidOptionsException('"taxonomy" must be an array of taxonomy names mapping to a list of terms, got '.get_debug_type($value));
            }

            $taxonomies = [];
            foreach ($value as $name => $terms) {
                $terms = is_array($terms) ? array_values($terms) : [$terms];
                $normalizedTerms = [];
                foreach ($terms as $term) {
                    if (!is_scalar($term)) {
                        throw new InvalidOptionsException(sprintf('Taxonomy "%s" contains a non-scalar term of type %s; taxonomy terms must be strings.', (string) $name, get_debug_type($term)));
                    }
                    $normalizedTerms[] = (string) $term;
                }
                sort($normalizedTerms);
                $taxonomies[(string) $name] = $normalizedTerms;
            }
            ksort($taxonomies);

            return $taxonomies;
        };
        $resolver->setNormalizer(self::KEY_TAXONOMY, $normalizeTaxonomy);
    }

    private function normalizeExtraValues(OptionsResolver $resolver): void
    {
        $normalizeExtra = static function (Options $options, mixed $value): array {
            if (null === $value) {
                return [];
            }
            if (!is_array($value)) {
                throw new InvalidOptionsException('"extra" must be an array, got '.get_debug_type($value));
            }
            self::assertSerializable($value, self::KEY_EXTRA);
            ksort($value);

            return $value;
        };
        $resolver->setNormalizer(self::KEY_EXTRA, $normalizeExtra);
    }

    private function normalizeChildDefaults(OptionsResolver $resolver): void
    {
        $normalizeChildDefaults = function (Options $options, mixed $value): array {
            if (null === $value || [] === $value) {
                return [];
            }
            if (!is_array($value)) {
                throw new InvalidOptionsException('"child_defaults" must be an array, got '.get_debug_type($value));
            }

            return $this->resolveChildDefaults($value);
        };
        $resolver->setNormalizer(self::KEY_CHILD_DEFAULTS, $normalizeChildDefaults);
    }

    /**
     * Child defaults are a partial set of page settings applied to child pages. Validate and
     * normalize them is if they were a page, but keep only the keys that were actually provided.
     *
     * @param array<array-key, mixed> $childDefaults
     *
     * @return array<string, mixed>
     */
    private function resolveChildDefaults(array $childDefaults): array
    {
        $provided = [];
        foreach ($childDefaults as $key => $value) {
            $provided[(string) $key] = $value;
        }

        $resolved = (new self($provided, $this->pageName.'-child'))->values->toArray();

        return array_intersect_key($resolved, $provided);
    }

    private function normalizeFileAliases(OptionsResolver $resolver): void
    {
        $normalizeFileAliases = static function (Options $options, mixed $value): array {
            if (null === $value || [] === $value) {
                return [];
            }
            if (!is_array($value)) {
                throw new InvalidOptionsException('"file_aliases" must be an array, got '.get_debug_type($value));
            }
            if (array_is_list($value)) {
                throw new InvalidOptionsException('"file_aliases" must be an associative array in the shape of `alias => existingFilename` , got a list');
            }
            try {
                Assert::allStringNotEmpty($value);
            } catch (InvalidArgumentException) {
                throw new InvalidOptionsException('"file_aliases" must be an associative array in the shape of `alias => existingFilename`');
            }

            return $value;
        };
        $resolver->setNormalizer(self::KEY_FILE_ALIASES, $normalizeFileAliases);
    }

    /**
     * Ensure the given value tree contains only serializable values:
     *  - scalars (int, float, string, bool)
     *  - null
     *  - DateTimeInterface instances
     *  - nested arrays of those
     *
     * @param array<array-key, mixed> $values
     */
    private static function assertSerializable(array $values, string $settingKey): void
    {
        foreach ($values as $value) {
            if (null === $value) {
                continue;
            }
            if (is_scalar($value)) {
                continue;
            }
            if ($value instanceof DateTimeInterface) {
                continue;
            }
            if (is_array($value)) {
                self::assertSerializable($value, $settingKey);

                continue;
            }

            throw new InvalidOptionsException(sprintf('Setting "%s" contains a non-serializable value of type %s; only scalars, dates, null and nested arrays of those are allowed.', $settingKey, get_debug_type($value)));
        }
    }
}
