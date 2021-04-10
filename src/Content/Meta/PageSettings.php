<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ZeroGravity\Cms\Content\Page;

class PageSettings
{
    private ?array $values = null;

    private string $pageName;

    public function __construct(array $values, string $pageName)
    {
        $this->pageName = $pageName;
        $this->validate($values);
    }

    /**
     * Get a single setting value.
     */
    public function get(string $name)
    {
        return $this->values[$name];
    }

    /**
     * Get array copy of all settings.
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Get all values that wouldn't have been set by default.
     */
    public function getNonDefaultValues(): array
    {
        $defaults = (new self([], $this->pageName))->toArray();

        $nonDefaults = [];
        foreach ($this->toArray() as $key => $value) {
            if (!array_key_exists($key, $defaults) || $defaults[$key] !== $value) {
                $nonDefaults[$key] = $value;
            }
        }

        return $nonDefaults;
    }

    /**
     * Resolve and validate page settings.
     * If everything was fine, assign them.
     */
    public function validate(array $values)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->values = $resolver->resolve($values);
        ksort($this->values);
    }

    /**
     * Configure validation rules for page settings.
     */
    private function configureOptions(OptionsResolver $resolver)
    {
        $this->configureDefaults($resolver);
        $this->configureAllowedTypes($resolver);
        $this->configureNormalizers($resolver);
    }

    private function configureDefaults(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'controller' => null,
            'extra' => [],
            'file_aliases' => [],
            'modular' => false,
            'module' => false,
            'visible' => false,
            'menu_id' => 'zero-gravity',
            'menu_label' => null,
            'date' => null,
            'publish' => true,
            'publish_date' => null,
            'unpublish_date' => null,
            'slug' => $this->pageName,
            'layout_template' => null,
            'content_template' => null,
            'title' => null,
            'taxonomy' => [],
            'content_type' => 'page',
            'child_defaults' => [],
        ]);
    }

    private function configureAllowedTypes(OptionsResolver $resolver): void
    {
        $resolver->setAllowedTypes('extra', ['null', 'array']);
        $resolver->setAllowedTypes('child_defaults', ['null', 'array']);
        $resolver->setAllowedTypes('file_aliases', ['null', 'array']);
        $resolver->setAllowedTypes('taxonomy', ['null', 'array']);
        $resolver->setAllowedTypes('visible', 'bool');
        $resolver->setAllowedTypes('modular', 'bool');
        $resolver->setAllowedTypes('module', 'bool');
        $resolver->setAllowedTypes('title', ['null', 'string']);
        $resolver->setAllowedTypes('layout_template', ['null', 'string']);
        $resolver->setAllowedTypes('content_template', ['null', 'string']);
        $resolver->setAllowedTypes('content_type', 'string');

        $dateTypes = ['null', 'string', 'int', DateTimeInterface::class];
        $resolver->setAllowedTypes('publish_date', $dateTypes);
        $resolver->setAllowedTypes('unpublish_date', $dateTypes);
        $resolver->setAllowedTypes('date', $dateTypes);
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
        $normalizeDateTime = function (Options $options, $value) {
            if (null === $value) {
                return $value;
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
        $normalizeTitle = function (Options $options, $value) {
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
        $normalizeTaxonomy = function (Options $options, $value) {
            if (null === $value) {
                return [];
            }
            $taxonomies = [];
            foreach ($value as $name => $taxonomy) {
                $taxonomies[$name] = array_values((array) $taxonomy);
            }

            return $taxonomies;
        };
        $resolver->setNormalizer('taxonomy', $normalizeTaxonomy);
    }

    private function normalizeArrayValues(OptionsResolver $resolver): void
    {
        $normalizeArray = function (Options $options, $value) {
            if (null === $value) {
                return [];
            }

            return $value;
        };
        $resolver->setNormalizer('extra', $normalizeArray);
        $resolver->setNormalizer('child_defaults', $normalizeArray);
        $resolver->setNormalizer('file_aliases', $normalizeArray);
    }
}
