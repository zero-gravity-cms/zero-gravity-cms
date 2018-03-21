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

        $this->assertSame($settings->toArray(), $nestedSettings->toArray());
    }

    /**
     * @test
     */
    public function settingsAreDefaultedAndCanBeFetched()
    {
        $settings = new PageSettings([], 'page');

        $expectedSettings = [
            'child_defaults' => [],
            'content_template' => null,
            'content_type' => 'page',
            'controller' => null,
            'date' => null,
            'extra' => [],
            'file_aliases' => [],
            'layout_template' => null,
            'locale' => null,
            'locales' => [],
            'menu_id' => 'zero-gravity',
            'menu_label' => null,
            'modular' => false,
            'module' => false,
            'publish' => true,
            'publish_date' => null,
            'slug' => 'page',
            'taxonomy' => [],
            'title' => 'Page',
            'unpublish_date' => null,
            'visible' => false,
        ];
        $this->assertEquals($expectedSettings, $settings->toArray());
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
        $this->assertEquals($expectedSettings, $settings->getNonDefaultValues());
    }
}
