<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Filesystem\ParsedDirectory;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

class DirectoryTest extends BaseUnit
{
    /**
     * @test
     * @dataProvider provideInvalidDirectories
     *
     * @param string $path
     */
    public function invalidDirectoryDataCausesException($path)
    {
        $dir = $this->createParsedDirectoryFromPath($this->getPageFixtureDir().'/'.$path);

        $this->expectException(StructureException::class);
        $dir->createPage(false, [], null);
    }

    public function provideInvalidDirectories()
    {
        return [
            ['invalid_pages/2_markdown_files'],
            ['invalid_pages/2_twig_files'],
            ['invalid_pages/2_yaml_files'],
            ['invalid_pages/basenames_dont_match__markdown'],
            ['invalid_pages/basenames_dont_match__twig'],
        ];
    }

    /**
     * @test
     */
    public function emptyDirectoryDataReturnsNoPage()
    {
        $dir = $this->createParsedDirectoryFromPath($this->getPageFixtureDir().'/invalid_pages/no_data');
        $page = $dir->createPage(false, [], null);

        $this->assertNull($page);
    }

    /**
     * @test
     * @dataProvider provideValidDirectories
     *
     * @param string $path
     */
    public function validDirectoryDataReturnsPage($path)
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/'.$path);
        $page = $dir->createPage(false, [], null);

        $this->assertInstanceOf(Page::class, $page);
    }

    public function provideValidDirectories()
    {
        return [
            ['01.yaml_only'],
            ['02.markdown_only'],
            ['03.yaml_and_markdown'],
            ['04.with_children'],
            ['05.twig_only'],
            ['06.yaml_and_twig'],
            ['no_sorting_prefix'],
        ];
    }

    /**
     * @test
     */
    public function yamlDataIsParsed()
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $dir->createPage(false, [], null);

        $this->assertSame('testtitle', $page->getTitle());
    }

    /**
     * @test
     */
    public function defaultSettingsCanBeSet()
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $dir->createPage(false, [
            'title' => 'defaulttitle',
            'menu_id' => 'defaultmenu',
        ], null);

        $this->assertSame('testtitle', $page->getTitle(), 'YAML settings override default settings');
        $this->assertSame('defaultmenu', $page->getMenuId(), 'default settings override empty settings');
    }

    /**
     * @test
     */
    public function createdPageContainsFiles()
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/04.with_children');
        $page = $dir->createPage(false, [], null);

        $this->assertEquals([
            'page.yaml',
            '03.empty/child_file5.png',
            '03.empty/child_file6.png',
            '03.empty/this_is_no_page.txt',
            '03.empty/sub/dir/child_file7.png',
            '03.empty/sub/dir/child_file8.png',
        ], array_keys($page->getFiles()));
    }

    /**
     * @param string $path
     *
     * @return ParsedDirectory
     */
    private function createParsedDirectoryFromPath(string $path)
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);

        return new ParsedDirectory(new \SplFileInfo($path), $fileFactory);
    }
}
