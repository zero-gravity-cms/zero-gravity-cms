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
     * @param array $values
     */
    public function __construct(array $values)
    {
        $values = $this->parseValues($values);
        $this->validate($values);
    }

    /**
     * @param $name
     *
     * @return bool
     */
    public function has(string $name): bool
    {
        return array_key_exists($name, $this->values);
    }

    /**
     * Get a single setting value or a default, if not defined.
     *
     * @param string $name
     * @param mixed  $default
     *
     * @return mixed
     */
    public function get(string $name, $default = null)
    {
        if ($this->has($name)) {
            return $this->values[$name];
        }

        return $default;
    }

    /**
     * Apply some simple default initializations.
     *
     * @param array $values
     *
     * @return array
     */
    private function parseValues(array $values)
    {
        if (isset($values['published_at']) && !$values['published_at'] instanceof DateTimeInterface) {
            $values['published_at'] = new DateTimeImmutable($values['published_at']);
        }

        return $values;
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
     * Configure validation rules for page settings.
     *
     * @param OptionsResolver $resolver
     */
    private function configureOptions(OptionsResolver $resolver)
    {
        $this->configureDefaults($resolver);
        $this->configureRequired($resolver);
        $this->configureAllowedTypes($resolver);
        $this->configureNormalizers($resolver);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureDefaults(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'menu_label' => null,
            'menu_id' => 'default',
            'template' => null,
            'controller' => null,
            'title' => null,
            'extra' => [],
            'is_visible' => false,
            'is_modular' => false,
            'file_aliases' => [],
            'published_at' => null,
        ]);
    }

    /**
     * @param OptionsResolver $resolver
     */
    private function configureRequired(OptionsResolver $resolver): void
    {
        $resolver->setRequired([
            'slug',
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
