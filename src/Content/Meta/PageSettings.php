<?php

namespace ZeroGravity\Cms\Content\Meta;

use DateTimeImmutable;
use DateTimeInterface;
use Symfony\Component\OptionsResolver\Options;
use Symfony\Component\OptionsResolver\OptionsResolver;

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
            'is_modular' => false,
            'is_visible' => false,
            'menu_id' => 'default',
            'menu_label' => null,
            'published_at' => null,
            'slug' => $this->pageName,
            'template' => null,
            'title' => null,
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureAllowedTypes(OptionsResolver $resolver): void
    {
        $resolver->setAllowedTypes('extra', 'array');
        $resolver->setAllowedTypes('file_aliases', 'array');
        $resolver->setAllowedTypes('is_visible', 'bool');
        $resolver->setAllowedTypes('is_modular', 'bool');
        $resolver->setAllowedTypes('published_at', ['null', 'string', DateTimeInterface::class]);
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
            }

            return new DateTimeImmutable((string) $value);
        };

        $resolver->setNormalizer('published_at', $normalizeDateTime);
    }
}
