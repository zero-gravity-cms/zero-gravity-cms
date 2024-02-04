<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Codeception\Attribute\DataProvider;
use Codeception\Attribute\Group;
use Iterator;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use SplFileInfo;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Filesystem\Directory;
use ZeroGravity\Cms\Filesystem\Event\AfterPageCreate;
use ZeroGravity\Cms\Filesystem\Event\BeforePageCreate;
use ZeroGravity\Cms\Filesystem\PageFactory;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

#[Group('pagefactory')]
class PageFactoryTest extends BaseUnit
{
    #[Test]
    public function emptyDirectoryDataReturnsNoPage(): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getPageFixtureDir().'/invalid_pages/no_data');
        $page = $pageFactory->createPage($dir, false, [], null);

        self::assertNull($page);
    }

    #[DataProvider('provideValidDirectories')]
    #[Test]
    public function validDirectoryDataReturnsPage(string $path): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/'.$path);
        $page = $pageFactory->createPage($dir, false, [], null);

        self::assertInstanceOf(Page::class, $page);
    }

    public static function provideValidDirectories(): Iterator
    {
        yield ['01.yaml_only'];
        yield ['02.markdown_only'];
        yield ['03.yaml_and_markdown_and_twig'];
        yield ['04.with_children'];
        yield ['05.twig_only'];
        yield ['06.yaml_and_twig'];
        yield ['no_sorting_prefix'];
    }

    #[Test]
    public function yamlDataIsParsed(): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [], null);

        self::assertSame('testtitle', $page->getTitle());
    }

    #[Test]
    public function defaultSettingsCanBeSet(): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [
            'title' => 'defaulttitle',
            'menu_id' => 'defaultmenu',
        ], null);

        self::assertSame('testtitle', $page->getTitle(), 'YAML settings override default settings');
        self::assertSame('defaultmenu', $page->getMenuId(), 'default settings override empty settings');
    }

    #[Test]
    public function createdPageContainsFiles(): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/04.with_children');
        $page = $pageFactory->createPage($dir, false, [], null);

        self::assertSame([
            'page.yaml',
            '03.empty/child_file5.png',
            '03.empty/child_file6.png',
            '03.empty/this_is_no_page.txt',
            '03.empty/sub/dir/child_file7.png',
            '03.empty/sub/dir/child_file8.png',
        ], array_keys($page->getFiles()));
    }

    #[Test]
    public function defaultTemplateIsSetIfSingleTwigFileWithBasenameIsPresent(): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/03.yaml_and_markdown_and_twig');
        $parentPage = null;
        $page = $pageFactory->createPage($dir, false, [], $parentPage);
        self::assertSame('@ZeroGravity/03.yaml_and_markdown_and_twig/name.html.twig', $page->getContentTemplate());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/06.yaml_and_twig');
        $parentPage = new Page('');
        $page = $pageFactory->createPage($dir, false, [], $parentPage);
        self::assertSame('@ZeroGravity/06.yaml_and_twig/page.html.twig', $page->getContentTemplate());
    }

    #[Test]
    public function pagesAreEqualIfParsedMultipleTimes(): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/04.with_children');
        $page1 = $pageFactory->createPage($dir, false, [], null);
        $page2 = $pageFactory->createPage($dir, false, [], null);

        self::assertEquals($page1, $page2);
    }

    #[Test]
    public function parentsChildDefaultsAreAppliedToChildPagesAndMerged(): void
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());
        $parentPage = new Page('page', [
            'child_defaults' => [
                'extra' => [
                    'some key' => 'some value',
                ],
            ],
        ]);

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [], $parentPage);

        self::assertSame('some value', $page->getExtra('some key'));
        self::assertSame('another_custom_value', $page->getExtra('custom'));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function eventsAreDispatchedDuringCreatePage(): void
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $start = true;
        $createPageCallbacks = static function ($argument) use (&$start): bool {
            if ($start) {
                if (!$argument instanceof BeforePageCreate) {
                    return false;
                }
                if ('01.yaml_only' !== $argument->getDirectory()->getName()) {
                    return false;
                }
                if ('yaml_only' !== $argument->getSettings()['slug']) {
                    return false;
                }
                if ($argument->getParentPage() instanceof Page) {
                    return false;
                }

                $start = false;

                return true;
            }
            if (!$argument instanceof AfterPageCreate) {
                return false;
            }

            return 'testtitle' === $argument->getPage()->getTitle();
        };

        $dispatcher->expects(self::exactly(2))
            ->method('dispatch')
            ->with(self::callback($createPageCallbacks))
            ->willReturnArgument(0)
        ;

        $pageFactory = new PageFactory(new NullLogger(), $dispatcher);
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $pageFactory->createPage($dir, false, []);
    }

    #[Test]
    public function settingsCanBeModifiedDuringBeforeCreatePage(): void
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(BeforePageCreate::class, static function (BeforePageCreate $event): void {
            $settings = $event->getSettings();
            $settings['extra']['very_custom_key'] = 'very custom value';
            $event->setSettings($settings);
        });

        $pageFactory = new PageFactory(new NullLogger(), $dispatcher);
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, []);

        self::assertSame('very custom value', $page->getExtra('very_custom_key'));
    }

    private function createParsedDirectoryFromPath(string $path): Directory
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $path);

        return new Directory(new SplFileInfo($path), $fileFactory, new NullLogger(), new EventDispatcher());
    }
}
