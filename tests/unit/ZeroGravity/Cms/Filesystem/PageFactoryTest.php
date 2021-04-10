<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Iterator;
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

/**
 * @group pagefactory
 */
class PageFactoryTest extends BaseUnit
{
    /**
     * @test
     */
    public function emptyDirectoryDataReturnsNoPage()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getPageFixtureDir().'/invalid_pages/no_data');
        $page = $pageFactory->createPage($dir, false, [], null);

        static::assertNull($page);
    }

    /**
     * @test
     * @dataProvider provideValidDirectories
     *
     * @param string $path
     */
    public function validDirectoryDataReturnsPage($path)
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/'.$path);
        $page = $pageFactory->createPage($dir, false, [], null);

        static::assertInstanceOf(Page::class, $page);
    }

    public function provideValidDirectories(): Iterator
    {
        yield ['01.yaml_only'];
        yield ['02.markdown_only'];
        yield ['03.yaml_and_markdown_and_twig'];
        yield ['04.with_children'];
        yield ['05.twig_only'];
        yield ['06.yaml_and_twig'];
        yield ['no_sorting_prefix'];
    }

    /**
     * @test
     */
    public function yamlDataIsParsed()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [], null);

        static::assertSame('testtitle', $page->getTitle());
    }

    /**
     * @test
     */
    public function defaultSettingsCanBeSet()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, [
            'title' => 'defaulttitle',
            'menu_id' => 'defaultmenu',
        ], null);

        static::assertSame('testtitle', $page->getTitle(), 'YAML settings override default settings');
        static::assertSame('defaultmenu', $page->getMenuId(), 'default settings override empty settings');
    }

    /**
     * @test
     */
    public function createdPageContainsFiles()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/04.with_children');
        $page = $pageFactory->createPage($dir, false, [], null);

        static::assertEquals([
            'page.yaml',
            '03.empty/child_file5.png',
            '03.empty/child_file6.png',
            '03.empty/this_is_no_page.txt',
            '03.empty/sub/dir/child_file7.png',
            '03.empty/sub/dir/child_file8.png',
        ], array_keys($page->getFiles()));
    }

    /**
     * @test
     */
    public function defaultTemplateIsSetIfSingleTwigFileWithBasenameIsPresent()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/03.yaml_and_markdown_and_twig');
        $parentPage = null;
        $page = $pageFactory->createPage($dir, false, [], $parentPage);
        static::assertEquals('@ZeroGravity/03.yaml_and_markdown_and_twig/name.html.twig', $page->getContentTemplate());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/06.yaml_and_twig');
        $parentPage = new Page('');
        $page = $pageFactory->createPage($dir, false, [], $parentPage);
        static::assertEquals('@ZeroGravity/06.yaml_and_twig/page.html.twig', $page->getContentTemplate());
    }

    /**
     * @test
     */
    public function pagesAreEqualIfParsedMultipleTimes()
    {
        $pageFactory = new PageFactory(new NullLogger(), new EventDispatcher());

        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/04.with_children');
        $page1 = $pageFactory->createPage($dir, false, [], null);
        $page2 = $pageFactory->createPage($dir, false, [], null);

        static::assertEquals($page1, $page2);
    }

    /**
     * @test
     */
    public function parentsChildDefaultsAreAppliedToChildPagesAndMerged()
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

        static::assertEquals('some value', $page->getExtra('some key'));
        static::assertEquals('another_custom_value', $page->getExtra('custom'));
    }

    /**
     * @test
     * @doesNotPerformAssertions
     */
    public function eventsAreDispatchedDuringCreatePage()
    {
        $dispatcher = $this->createMock(EventDispatcher::class);

        $start = true;
        $createPageCallbacks = function ($argument) use (&$start) {
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
                if (null !== $argument->getParentPage()) {
                    return false;
                }

                $start = false;

                return true;
            }

            if (!$argument instanceof AfterPageCreate) {
                return false;
            }
            if ('testtitle' !== $argument->getPage()->getTitle()) {
                return false;
            }

            return true;
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

    /**
     * @test
     */
    public function settingsCanBeModifiedDuringBeforeCreatePage()
    {
        $dispatcher = new EventDispatcher();
        $dispatcher->addListener(BeforePageCreate::class, function (BeforePageCreate $event) {
            $settings = $event->getSettings();
            $settings['extra']['very_custom_key'] = 'very custom value';
            $event->setSettings($settings);
        });

        $pageFactory = new PageFactory(new NullLogger(), $dispatcher);
        $dir = $this->createParsedDirectoryFromPath($this->getValidPagesDir().'/01.yaml_only');
        $page = $pageFactory->createPage($dir, false, []);

        static::assertSame('very custom value', $page->getExtra('very_custom_key'));
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
