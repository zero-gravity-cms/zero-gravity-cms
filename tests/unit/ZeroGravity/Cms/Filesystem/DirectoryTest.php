<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Iterator;
use Psr\Log\NullLogger;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcher;
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
        $dir = new Directory(new SplFileInfo($path), $fileFactory, new NullLogger(), new EventDispatcher(), $parentPath);

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
        $dir = new Directory(new SplFileInfo($path), $fileFactory, new NullLogger(), new EventDispatcher(), $parentPath);

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

    public function provideInvalidDirectories(): Iterator
    {
        yield '2 markdown files' => ['invalid_pages/2_markdown_files'];
        yield '2 yaml files' => ['invalid_pages/2_yaml_files'];
        yield 'yaml and markdown basenames do not match' => ['invalid_pages/basenames_dont_match__markdown'];
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
     * @test
     * @dataProvider providePathsAndStrategies
     *
     * @param string $path
     * @param string $expectedStrategy
     */
    public function contentStrategyIsDetectedCorrectly($path, $expectedStrategy)
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().$path);

        $this->assertSame($expectedStrategy, $dir->getContentStrategy());
    }

    public function providePathsAndStrategies(): Iterator
    {
        yield '01.yaml_only' => [
            '/01.yaml_only',
            Directory::CONTENT_STRATEGY_YAML_ONLY,
        ];
        yield '02.markdown_only' => [
            '/02.markdown_only',
            Directory::CONTENT_STRATEGY_MARKDOWN_ONLY,
        ];
        yield '03.yaml_and_markdown_and_twig' => [
            '/03.yaml_and_markdown_and_twig',
            Directory::CONTENT_STRATEGY_YAML_AND_MARKDOWN,
        ];
        yield '05.twig_only' => [
            '/05.twig_only',
            Directory::CONTENT_STRATEGY_TWIG_ONLY,
        ];
        yield '06.yaml_and_twig' => [
            '/06.yaml_and_twig',
            Directory::CONTENT_STRATEGY_YAML_ONLY,
        ];
        yield 'images' => [
            '/images',
            Directory::CONTENT_STRATEGY_NONE,
        ];
    }

    /**
     * @return Directory
     */
    private function createParsedDirectoryFromPath(string $path)
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);

        return new Directory(new SplFileInfo($path), $fileFactory, new NullLogger(), new EventDispatcher());
    }
}
