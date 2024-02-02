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

        static::assertFalse($diff->filesystemPathHasChanged());
        static::assertFalse($diff->contentHasChanged());
        static::assertFalse($diff->settingsHaveChanged());

        static::assertSame($newPage, $diff->getNew());

        $newPage->setName('foo');
        static::assertTrue($diff->filesystemPathHasChanged());
        static::assertSame('/foo', $diff->getNewFilesystemPath());

        $newPage->setName('page');
        static::assertFalse($diff->filesystemPathHasChanged());

        $newParent = new Page('some-path');
        $newPage->setParent($newParent);
        static::assertTrue($diff->filesystemPathHasChanged());
        static::assertSame('/some-path/page', $diff->getNewFilesystemPath());

        $newPage->setContentRaw('test');
        static::assertTrue($diff->contentHasChanged());
        static::assertSame('test', $diff->getNewContentRaw());

        $settings = $newPage->getSettings();
        $settings['menu_id'] = 'another-menu';
        $newPage->setSettings($settings);

        static::assertTrue($diff->settingsHaveChanged());
        static::assertSame(['menu_id' => 'another-menu'], $diff->getNewNonDefaultSettings());
    }
}
