<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\BasicWritablePageTrait;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\WritablePage;

/**
 * @group page
 */
class PageDiffTest extends BaseUnit
{
    /**
     * @test
     */
    public function pageChangesAreDetected()
    {
        $oldPage = new class('page') extends Page implements WritablePage {
            use BasicWritablePageTrait;
        };
        $newPage = new class('page') extends Page implements WritablePage {
            use BasicWritablePageTrait;
        };

        $diff = new PageDiff($oldPage, $newPage);

        $this->assertFalse($diff->nameHasChanged());
        $this->assertFalse($diff->contentHasChanged());
        $this->assertFalse($diff->settingsHaveChanged());

        $this->assertSame($newPage, $diff->getNew());

        $newPage->setName('foo');
        $this->assertTrue($diff->nameHasChanged());
        $this->assertSame('foo', $diff->getNewName());

        $newPage->setContentRaw('test');
        $this->assertTrue($diff->contentHasChanged());
        $this->assertSame('test', $diff->getNewContentRaw());

        $settings = $newPage->getSettings();
        $settings['menu_id'] = 'another-menu';
        $newPage->setSettings($settings);

        $this->assertTrue($diff->settingsHaveChanged());
        $this->assertSame($settings, $diff->getNewSettings());
    }
}
