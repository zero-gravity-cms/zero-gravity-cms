<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\File;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Filesystem\Directory;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

/**
 * @group directory
 */
class DirectoryTest extends BaseUnit
{
    /**
     * @test
     */
    public function getPathReturnsEmptyPathWithoutParentPath()
    {
        $path = $this->getValidPagesDir().'/01.yaml_only';
        $parentPath = null;
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $dir = new Directory(new \SplFileInfo($path), $fileFactory, $parentPath);

        $this->assertSame('', $dir->getPath());
    }

    /**
     * @test
     */
    public function getPathReturnsPathWithAddedParentPath()
    {
        $path = $this->getValidPagesDir().'/01.yaml_only';
        $parentPath = 'some/path';
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $dir = new Directory(new \SplFileInfo($path), $fileFactory, $parentPath);

        $this->assertSame('some/path/01.yaml_only', $dir->getPath());
    }

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
        $dir->validateFiles();
    }

    public function provideInvalidDirectories()
    {
        return [
            '2 markdown files' => ['invalid_pages/2_markdown_files'],
            '2 yaml files' => ['invalid_pages/2_yaml_files'],
            'yaml and markdown basenames do not match' => ['invalid_pages/basenames_dont_match__markdown'],
        ];
    }

    /**
     * @test
     */
    public function getDefaultBasenameReturnsYamlBasenameIfPresent()
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $this->assertSame('page', $dir->getDefaultBasename());
    }

    /**
     * @test
     */
    public function getDefaultBasenameReturnsMarkdownBasenameIfPresent()
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/02.markdown_only');
        $this->assertSame('page', $dir->getDefaultBasename());
    }

    /**
     * @test
     */
    public function getDefaultBasenameTwigFileReturnsTwigFileIfMatchesDefaultBasename()
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/03.yaml_and_markdown_and_twig');
        $this->assertInstanceOf(File::class, $dir->getDefaultBasenameTwigFile());
        $this->assertSame('/name.html.twig', $dir->getDefaultBasenameTwigFile()->getPathname());
    }

    /**
     * @param string $path
     *
     * @return Directory
     */
    private function createParsedDirectoryFromPath(string $path)
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $directory = new Directory(new \SplFileInfo($path), $fileFactory);

        return $directory;
    }
}
