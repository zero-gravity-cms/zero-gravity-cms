<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;
use ZeroGravity\Cms\Content\Page;

class PageSettings
{
    /**
     * @var array
     */
    private $values;

    /**
     * @var string
     */
    private $pageName;

    /**
     * @param array  $values
     * @param string $pageName
     */
    public function __construct(array $values, string $pageName)
    {
        $this->pageName = $pageName;
        $this->validate($values);
    }

    /**
     * Get a single setting value.
     *
     * @param string $name
     *
     * @return mixed
     */
    public function get(string $name)
    {
        return $this->values[$name];
    }

    /**
     * Get array copy of all settings.
     *
     * @return array
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * Resolve and validate page settings.
     * If everything was fine, assign them.
     *
     * @param array $values
     */
    public function validate(array $values)
    {
        $resolver = new OptionsResolver();
        $this->configureOptions($resolver);

        $this->values = $resolver->resolve($values);
    }

    /**
     * Configure validation rules for page settings.
     *
     * @param OptionsResolver $resolver
     */
    private function configureOptions(OptionsResolver $resolver)
    {
        $this->configureDefaults($resolver);
        $this->configureAllowedTypes($resolver);
        $this->configureNormalizers($resolver);
    }

    /**
     * @param OptionsResolver $resolver
     */
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
            'template' => null,
            'title' => null,
            'taxonomy' => [],
            'content_type' => 'page',
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureAllowedTypes(OptionsResolver $resolver): void
    {
        $resolver->setAllowedTypes('extra', 'array');
        $resolver->setAllowedTypes('file_aliases', 'array');
        $resolver->setAllowedTypes('taxonomy', ['null', 'array']);
        $resolver->setAllowedTypes('visible', 'bool');
        $resolver->setAllowedTypes('modular', 'bool');
        $resolver->setAllowedTypes('module', 'bool');
        $resolver->setAllowedTypes('title', ['null', 'string']);
        $resolver->setAllowedTypes('content_type', 'string');

        $dateTypes = ['null', 'string', 'int', DateTimeInterface::class];
        $resolver->setAllowedTypes('publish_date', $dateTypes);
        $resolver->setAllowedTypes('unpublish_date', $dateTypes);
        $resolver->setAllowedTypes('date', $dateTypes);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureNormalizers(OptionsResolver $resolver): void
    {
        $normalizeDateTime = function (Options $options, $value) {
            if (null === $value) {
                return $value;
            }
            if ($value instanceof DateTimeImmutable) {
                return $value;
            } elseif ($value instanceof DateTimeInterface) {
                $value = $value->format('c');
            } elseif (is_int($value)) {
                $value = '@'.$value;
            }

            return new DateTimeImmutable((string) $value);
        };

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

        $resolver->setNormalizer('date', $normalizeDateTime);
        $resolver->setNormalizer('publish_date', $normalizeDateTime);
        $resolver->setNormalizer('unpublish_date', $normalizeDateTime);
        $resolver->setNormalizer('title', $normalizeTitle);
        $resolver->setNormalizer('taxonomy', $normalizeTaxonomy);
    }
}
