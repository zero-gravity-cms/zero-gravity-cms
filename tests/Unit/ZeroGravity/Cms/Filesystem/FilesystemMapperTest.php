<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Filesystem\FilesystemMapper;
use ZeroGravity\Cms\Filesystem\WritableFilesystemPage;

class FilesystemMapperTest extends BaseUnit
{
    /**
     * @test
     */
    public function parserThrowsExceptionIfDirectoryDoesNotExist()
    {
        $path = $this->getPageFixtureDir().'/invalid_path';
        $fileFactory = $this->getDefaultFileFactory();
        $mapper = new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $this->expectException(FilesystemException::class);
        $mapper->parse();
    }

    /**
     * @test
     */
    public function parserReturnsPagesIfContentIsValid()
    {
        $path = $this->getValidPagesDir();
        $fileFactory = $this->getDefaultFileFactory();
        $mapper = new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $pages = $mapper->parse();
        static::assertContainsOnlyInstancesOf(Page::class, $pages);
        static::assertCount(8, $pages);
    }

    /**
     * @test
     *
     * @group write
     */
    public function parserReturnsWritablePage()
    {
        $path = $this->getValidPagesDir();
        $fileFactory = $this->getDefaultFileFactory();
        $mapper = new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];

        $writablePage = $mapper->getWritablePageInstance($page);
        static::assertInstanceOf(WritableFilesystemPage::class, $writablePage);
        static::assertSame('01.yaml_only', $writablePage->getName());
    }
}
