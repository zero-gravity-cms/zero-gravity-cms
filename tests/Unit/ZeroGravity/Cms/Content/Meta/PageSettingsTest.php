<?php

namespace Tests\Unit\ZeroGravity\Cms\Content\Meta;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Meta\PageSettings;

/**
 * @group meta
 */
class PageSettingsTest extends BaseUnit
{
    /**
     * @test
     */
    public function settingsAreIdempotent()
    {
        $settings = new PageSettings([
            'publish_date' => time(),
        ], 'page');
        $nestedSettings = new PageSettings($settings->toArray(), 'page');

        static::assertSame($settings->toArray(), $nestedSettings->toArray());
    }

    /**
     * @test
     */
    public function settingsAreDefaultedAndCanBeFetched()
    {
        $settings = new PageSettings([], 'page');

        $expectedSettings = [
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
        static::assertEquals($expectedSettings, $settings->toArray());
    }

    /**
     * @test
     */
    public function settingsCanBeFetchedWithoutDefaultValues()
    {
        $settings = new PageSettings([
            'slug' => 'not-page',
            'menu_label' => 'custom label',
        ], 'page');

        $expectedSettings = [
            'slug' => 'not-page',
            'menu_label' => 'custom label',
        ];
        static::assertEquals($expectedSettings, $settings->getNonDefaultValues());
    }
}
