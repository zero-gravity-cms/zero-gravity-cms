<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Codeception\Attribute\Group;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\PageSettings;

#[Group('meta')]
class PageSettingsTest extends BaseUnit
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
        $settings = new PageSettings([
            'publish_date' => time(),
        ], 'page');
        $nestedSettings = new PageSettings($settings->toArray(), 'page');

        self::assertSame($settings->toArray(), $nestedSettings->toArray());
    }

    #[Test]
    public function settingsAreDefaultedAndCanBeFetched(): void
    {
        $settings = new PageSettings([], 'page');

        self::assertEquals(self::DEFAULT_SETTINGS, $settings->toArray());
    }

    #[Test]
    public function settingsCanBeFetchedWithoutDefaultValuesAndWillBeSortedByKey(): void
    {
        $settings = new PageSettings([
            'slug' => 'not-page',
            'menu_label' => 'custom label',
        ], 'page');

        $expectedSettings = [
            'menu_label' => 'custom label',
            'slug' => 'not-page',
        ];

        self::assertSame($expectedSettings, $settings->getNonDefaultValues());
    }

    #[Test]
    public function settingsCanBeSerializedAndWillBeSortedByKey(): void
    {
        $settings = new PageSettings([
            'slug' => 'not-page',
            'taxonomy' => [
                'tags' => [
                    'foo',
                    'bar',
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
                'this' => 'that',
                'or' => 'those',
            ],
            'menu_label' => 'custom label',
            'slug' => 'not-page',
            'taxonomy' => [
                'groups' => [
                    'group 1',
                    'group 3',
                ],
                'tags' => [
                    'foo',
                    'bar',
                ],
            ],
        ];

        self::assertSame($expectedSettings, $settings->getNonDefaultValues(true));

        $expectedSettingsWithDefaults = array_merge(self::DEFAULT_SETTINGS, $expectedSettings);
        self::assertEquals($expectedSettingsWithDefaults, $settings->toArray(true));
    }
}
