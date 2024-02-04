<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Filesystem\FilesystemMapper;
use ZeroGravity\Cms\Filesystem\WritableFilesystemPage;

class FilesystemMapperTest extends BaseUnit
{
    #[Test]
    public function parserThrowsExceptionIfDirectoryDoesNotExist(): void
    {
        $path = $this->getPageFixtureDir().'/invalid_path';
        $fileFactory = $this->getDefaultFileFactory();
        $mapper = new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $this->expectException(FilesystemException::class);
        $mapper->parse();
    }

    #[Test]
    public function parserReturnsPagesIfContentIsValid(): void
    {
        $path = $this->getValidPagesDir();
        $fileFactory = $this->getDefaultFileFactory();
        $mapper = new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $pages = $mapper->parse();
        self::assertContainsOnlyInstancesOf(Page::class, $pages);
        self::assertCount(8, $pages);
    }

    #[Group('write')]
    #[Test]
    public function parserReturnsWritablePage(): void
    {
        $path = $this->getValidPagesDir();
        $fileFactory = $this->getDefaultFileFactory();
        $mapper = new FilesystemMapper($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];

        $writablePage = $mapper->getWritablePageInstance($page);
        self::assertInstanceOf(WritableFilesystemPage::class, $writablePage);
        self::assertSame('01.yaml_only', $writablePage->getName());
    }
}
