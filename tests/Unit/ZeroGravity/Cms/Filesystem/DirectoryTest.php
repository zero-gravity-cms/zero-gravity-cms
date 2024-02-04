<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Iterator;
use PHPUnit\Framework\Attributes\Test;
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

#[Group('directory')]
class DirectoryTest extends BaseUnit
{
    #[Test]
    public function getPathReturnsEmptyPathWithoutParentPath(): void
    {
        $path = $this->getValidPagesDir().'/01.yaml_only';
        $parentPath = null;
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $dir = new Directory(new SplFileInfo($path), $fileFactory, new NullLogger(), new EventDispatcher(), $parentPath);

        self::assertSame('', $dir->getPath());
    }

    #[Test]
    public function getPathReturnsPathWithAddedParentPath(): void
    {
        $path = $this->getValidPagesDir().'/01.yaml_only';
        $parentPath = 'some/path';
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);
        $dir = new Directory(new SplFileInfo($path), $fileFactory, new NullLogger(), new EventDispatcher(), $parentPath);

        self::assertSame('some/path/01.yaml_only', $dir->getPath());
    }

    #[DataProvider('provideInvalidDirectories')]
    #[Test]
    public function invalidDirectoryDataCausesException(string $path): void
    {
        $dir = $this->createParsedDirectoryFromPath($this->getPageFixtureDir().'/'.$path);
        $this->expectException(StructureException::class);
        $dir->validateFiles();
    }

    public static function provideInvalidDirectories(): Iterator
    {
        yield '2 markdown files' => ['invalid_pages/2_markdown_files'];
        yield '2 yaml files' => ['invalid_pages/2_yaml_files'];
        yield 'yaml and markdown basenames do not match' => ['invalid_pages/basenames_dont_match__markdown'];
    }

    #[Test]
    public function getDefaultBasenameReturnsYamlBasenameIfPresent(): void
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        self::assertSame('page', $dir->getDefaultBasename());
    }

    #[Test]
    public function getDefaultBasenameReturnsMarkdownBasenameIfPresent(): void
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/02.markdown_only');
        self::assertSame('page', $dir->getDefaultBasename());
    }

    #[Test]
    public function getDefaultBasenameTwigFileReturnsTwigFileIfMatchesDefaultBasename(): void
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/03.yaml_and_markdown_and_twig');
        self::assertInstanceOf(File::class, $dir->getDefaultBasenameTwigFile());
        self::assertSame('/name.html.twig', $dir->getDefaultBasenameTwigFile()->getPathname());
    }

    /**
     * @param string $expectedStrategy
     */
    #[DataProvider('providePathsAndStrategies')]
    #[Test]
    public function contentStrategyIsDetectedCorrectly(string $path, mixed $expectedStrategy): void
    {
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().$path);

        self::assertSame($expectedStrategy, $dir->getContentStrategy());
    }

    public static function providePathsAndStrategies(): Iterator
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

    private function createParsedDirectoryFromPath(string $path): Directory
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);

        return new Directory(new SplFileInfo($path), $fileFactory, new NullLogger(), new EventDispatcher());
    }
}
