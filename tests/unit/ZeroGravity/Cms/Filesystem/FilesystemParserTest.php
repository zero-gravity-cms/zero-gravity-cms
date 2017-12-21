<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Filesystem\FilesystemParser;

class FilesystemParserTest extends BaseUnit
{
    /**
     * @test
     */
    public function parserThrowsExceptionIfDirectoryDoesNotExist()
    {
        $path = $this->getPageFixtureDir().'/invalid_path';
        $fileFactory = $this->getDefaultFileFactory();
        $parser = new FilesystemParser($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $this->expectException(FilesystemException::class);
        $parser->parse();
    }

    /**
     * @test
     */
    public function parserReturnsPagesIfContentIsValid()
    {
        $path = $this->getValidPagesDir();
        $fileFactory = $this->getDefaultFileFactory();
        $parser = new FilesystemParser($fileFactory, $path, false, [], new NullLogger(), new EventDispatcher());

        $pages = $parser->parse();
        $this->assertContainsOnlyInstancesOf(Page::class, $pages);
        $this->assertCount(8, $pages);
    }
}
