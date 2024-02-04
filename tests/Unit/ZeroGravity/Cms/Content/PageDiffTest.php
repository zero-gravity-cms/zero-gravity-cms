<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Attributes\Test;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\BasicWritablePageTrait;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\WritablePage;

#[Group('page')]
class PageDiffTest extends BaseUnit
{
    #[Test]
    public function pageChangesAreDetected(): void
    {
        $oldPage = new class('page') extends Page implements WritablePage {
            use BasicWritablePageTrait;
        };
        $newPage = new class('page') extends Page implements WritablePage {
            use BasicWritablePageTrait;
        };

        $diff = new PageDiff($oldPage, $newPage);

        self::assertFalse($diff->filesystemPathHasChanged());
        self::assertFalse($diff->contentHasChanged());
        self::assertFalse($diff->settingsHaveChanged());

        self::assertSame($newPage, $diff->getNew());

        $newPage->setName('foo');
        self::assertTrue($diff->filesystemPathHasChanged());
        self::assertSame('/foo', $diff->getNewFilesystemPath());

        $newPage->setName('page');
        self::assertFalse($diff->filesystemPathHasChanged());

        $newParent = new Page('some-path');
        $newPage->setParent($newParent);
        self::assertTrue($diff->filesystemPathHasChanged());
        self::assertSame('/some-path/page', $diff->getNewFilesystemPath());

        $newPage->setContentRaw('test');
        self::assertTrue($diff->contentHasChanged());
        self::assertSame('test', $diff->getNewContentRaw());

        $settings = $newPage->getSettings();
        $settings['menu_id'] = 'another-menu';
        $newPage->setSettings($settings);

        self::assertTrue($diff->settingsHaveChanged());
        self::assertSame(['menu_id' => 'another-menu'], $diff->getNewNonDefaultSettings());
    }
}
