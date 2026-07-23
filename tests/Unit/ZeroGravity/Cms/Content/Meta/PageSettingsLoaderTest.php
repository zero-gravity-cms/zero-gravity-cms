<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Codeception\Attribute\Group;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use stdClass;
use Symfony\Component\OptionsResolver\Exception\InvalidOptionsException;
use Symfony\Component\OptionsResolver\Exception\UndefinedOptionsException;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\PageSettingsLoader;
use ZeroGravity\Cms\Content\Meta\SettingValuesSerializer;

#[Group('meta')]
class PageSettingsLoaderTest extends BaseUnit
{
    private const DEFAULT_SETTINGS = [
        'slug' => 'page',
        'title' => 'Page',
        'visible' => false,
        'modular' => false,
        'module' => false,
        'layout_template' => null,
        'content_template' => null,
        'controller' => null,
        'menu_id' => 'zero-gravity',
        'menu_label' => null,
        'file_aliases' => [],
        'date' => null,
        'publish' => true,
        'publish_date' => null,
        'unpublish_date' => null,
        'extra' => [],
        'taxonomy' => [],
        'content_type' => 'page',
        'child_defaults' => [],
    ];

    #[Test]
    public function settingsAreIdempotent(): void
    {
        $settingsLoader = new PageSettingsLoader([
            'publish_date' => time(),
        ], 'page');
        $nestedSettings = new PageSettingsLoader($settingsLoader->values->toArray(), 'page');

        self::assertSame($settingsLoader->values->toArray(), $nestedSettings->values->toArray());
    }

    #[Test]
    public function settingsAreDefaultedAndCanBeFetched(): void
    {
        $settings = new PageSettingsLoader([], 'page');

        self::assertEquals(self::DEFAULT_SETTINGS, $settings->values->toArray());
    }

    #[Test]
    public function settingsCanBeFetchedWithoutDefaultValuesAndWillBeSortedByKey(): void
    {
        $settings = new PageSettingsLoader([
            'slug' => 'not-page',
            'menu_label' => 'custom label',
        ], 'page');

        self::assertSame([
            'menu_label' => 'custom label',
            'slug' => 'not-page',
        ], $settings->getNonDefaultValues());
    }

    #[Test]
    public function nonDefaultValuesCanBeFilteredFurther(): void
    {
        $settings = new PageSettingsLoader([
            'slug' => 'not-page',
            'menu_label' => 'custom label',
            'menu_id' => 'zero-gravity',
        ], 'page');

        self::assertSame([
            'menu_label' => 'custom label',
            'slug' => 'not-page',
        ], $settings->getNonDefaultValues());

        self::assertSame([
            'slug' => 'not-page',
        ], $settings->getNonDefaultValues(
            excludeMatchingValues: ['menu_label' => 'custom label'],
        ));
    }

    #[Test]
    public function settingsCanBeSerializedAndWillBeSortedByKey(): void
    {
        $settings = new PageSettingsLoader([
            'slug' => 'not-page',
            'taxonomy' => [
                'tags' => [
                    'bar',
                    'foo',
                ],
                'groups' => [
                    'group 1',
                    'group 3',
                ],
            ],
            'extra' => [
                'this' => 'that',
                'or' => 'those',
            ],
            'menu_label' => 'custom label',
            'date' => new DateTimeImmutable('2024-01-01'),
        ], 'page');

        $expectedSettings = [
            'date' => '2024-01-01 00:00:00',
            'extra' => [
                'or' => 'those',
                'this' => 'that',
            ],
            'menu_label' => 'custom label',
            'slug' => 'not-page',
            'taxonomy' => [
                'groups' => [
                    'group 1',
                    'group 3',
                ],
                'tags' => [
                    'bar',
                    'foo',
                ],
            ],
        ];

        self::assertSame($expectedSettings, $settings->getNonDefaultValues(true));

        $expectedSettingsWithDefaults = array_merge(self::DEFAULT_SETTINGS, $expectedSettings);
        self::assertEquals($expectedSettingsWithDefaults, SettingValuesSerializer::serialize($settings->values)->toArray());
    }

    #[Test]
    public function extraAcceptsNestedScalarsDatesAndNullAndSerializesRecursively(): void
    {
        $settings = new PageSettingsLoader([
            'extra' => [
                'string' => 'value',
                'int' => 42,
                'float' => 3.14,
                'bool' => true,
                'nothing' => null,
                'date' => new DateTimeImmutable('2024-01-01 10:00:00'),
                'nested' => [
                    'deep' => 'value',
                    'deep_date' => new DateTimeImmutable('2024-02-02 12:00:00'),
                ],
            ],
        ], 'page');

        $extra = $settings->values->extra;
        self::assertSame(42, $extra['int']);
        self::assertSame(3.14, $extra['float']);
        self::assertTrue($extra['bool']);
        self::assertNull($extra['nothing']);
        self::assertInstanceOf(DateTimeImmutable::class, $extra['date']);

        // serialization converts dates to strings at any depth and leaves other scalars untouched
        $serialized = SettingValuesSerializer::serialize($settings->values)->extra;
        self::assertIsArray($serialized['nested']);
        self::assertSame('2024-01-01 10:00:00', $serialized['date']);
        self::assertSame('2024-02-02 12:00:00', $serialized['nested']['deep_date'] ?? null);
        self::assertSame(42, $serialized['int']);
        self::assertSame(3.14, $serialized['float']);
    }

    #[Test]
    public function extraRejectsNonSerializableObjects(): void
    {
        $this->expectException(InvalidOptionsException::class);

        new PageSettingsLoader([
            'extra' => [
                'object' => new stdClass(),
            ],
        ], 'page');
    }

    #[Test]
    public function extraRejectsNonSerializableObjectsWhenNested(): void
    {
        $this->expectException(InvalidOptionsException::class);

        new PageSettingsLoader([
            'extra' => [
                'nested' => [
                    'object' => new stdClass(),
                ],
            ],
        ], 'page');
    }

    #[Test]
    public function taxonomyCoercesScalarTermsToSortedStringLists(): void
    {
        $settings = new PageSettingsLoader([
            'taxonomy' => [
                'tag' => ['b', 'a'],
                'year' => [2024, 2023],
                'single' => 'lonely',
            ],
        ], 'page');

        $taxonomy = $settings->values->taxonomy;
        self::assertSame(['a', 'b'], $taxonomy['tag']);
        self::assertSame(['2023', '2024'], $taxonomy['year']);
        self::assertSame(['lonely'], $taxonomy['single']);
    }

    #[Test]
    public function taxonomyRejectsNonScalarTerms(): void
    {
        $this->expectException(InvalidOptionsException::class);

        new PageSettingsLoader([
            'taxonomy' => [
                'tag' => [['nested' => 'array']],
            ],
        ], 'page');
    }

    #[Test]
    public function childDefaultsAreNormalizedButKeepOnlyProvidedKeys(): void
    {
        $settings = new PageSettingsLoader([
            'child_defaults' => [
                'content_type' => 'article',
                'date' => '2024-01-01',
            ],
        ], 'page');

        $childDefaults = $settings->values->child_defaults;
        self::assertSame(['content_type', 'date'], array_keys($childDefaults));
        self::assertSame('article', $childDefaults['content_type']);
        self::assertInstanceOf(DateTimeImmutable::class, $childDefaults['date']);
    }

    #[Test]
    public function childDefaultsRejectUnknownKeys(): void
    {
        $this->expectException(UndefinedOptionsException::class);

        new PageSettingsLoader([
            'child_defaults' => [
                'not_a_real_setting' => 'value',
            ],
        ], 'page');
    }

    #[Test]
    public function childDefaultsAreValidatedRecursively(): void
    {
        $this->expectException(InvalidOptionsException::class);

        new PageSettingsLoader([
            'child_defaults' => [
                'extra' => [
                    'object' => new stdClass(),
                ],
            ],
        ], 'page');
    }
}
