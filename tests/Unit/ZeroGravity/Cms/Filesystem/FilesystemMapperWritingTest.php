<?php

namespace Tests\Unit\ZeroGravity\Cms\Filesystem;

use Codeception\Attribute\Group;
use PHPUnit\Framework\Attributes\Test;
use Psr\Log\NullLogger;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use Tests\Unit\ZeroGravity\Cms\Test\TempDirTrait;
use ZeroGravity\Cms\Content\BasicWritablePageTrait;
use ZeroGravity\Cms\Content\FileFactory;
use ZeroGravity\Cms\Content\FileTypeDetector;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\WritablePage;
use ZeroGravity\Cms\Exception\FilesystemException;
use ZeroGravity\Cms\Exception\StructureException;
use ZeroGravity\Cms\Filesystem\FilesystemMapper;
use ZeroGravity\Cms\Filesystem\YamlMetadataLoader;

#[Group('write')]
class FilesystemMapperWritingTest extends BaseUnit
{
    use TempDirTrait;

    protected function _before()
    {
        $this->setupTempDir($this->getValidPagesDir());
    }

    protected function _after()
    {
        $this->cleanupTempDir();
    }

    #[Test]
    public function savingThrowsExceptionIfDiffDoesNotContainFilesystemPages(): void
    {
        $oldPage = new class('page') extends Page implements WritablePage {
            use BasicWritablePageTrait;
        };
        $newPage = clone $oldPage;
        $diff = new PageDiff($oldPage, $newPage);

        $mapper = $this->getTempValidPagesFilesystemMapper();
        $this->expectException(FilesystemException::class);
        $mapper->saveChanges($diff);
    }

    #[Test]
    public function savingThrowsExceptionIfNewNameAlreadyExists(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setName('02.markdown_only');

        $diff = new PageDiff($oldPage, $newPage);

        $this->expectException(StructureException::class);
        $mapper->saveChanges($diff);
    }

    #[Test]
    public function contentIsSavedToExistingMarkdownFile(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];
        self::assertCount(3, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw('new **raw** content');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];

        self::assertSame('<p>new <strong>raw</strong> content</p>', $page->getContent());
        self::assertCount(3, $page->getFiles());
    }

    #[Test]
    public function contentIsSavedToExistingMarkdownFileWithFrontmatterYaml(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];
        self::assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw($oldPage->getContentRaw()."\n\nnew **raw** content");

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];

        self::assertSame("<p>This is the content of page 02.</p>\n<p>new <strong>raw</strong> content</p>", $page->getContent());
        self::assertSame('value-kept-after-content-update', $page->getExtra('keep-value'));
        self::assertCount(1, $page->getFiles());
    }

    #[Test]
    public function contentIsSavedToDirectoryContainingOnlyYamlFile(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];
        self::assertCount(4, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw('new **raw** content');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];

        self::assertSame('<p>new <strong>raw</strong> content</p>', $page->getContent());
        self::assertCount(5, $page->getFiles());
    }

    #[Test]
    public function contentIsSavedToDirectoryContainingOnlyTwigFile(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];
        self::assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setContentRaw('new **raw** content');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];

        self::assertSame('<p>new <strong>raw</strong> content</p>', $page->getContent());
        self::assertCount(2, $page->getFiles());
    }

    #[Test]
    public function settingsAreSavedToExistingYamlFile(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];
        self::assertCount(3, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;

        $settings = $oldPage->getSettings();
        $settings['date'] = '2018-03-14 00:00:00+0000';
        $settings['extra']['new-key'] = 'new-value';
        $newPage->setSettings($settings);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_and_markdown_and_twig'];

        self::assertSame('2018-03-14', $page->getDate()->format('Y-m-d'));
        self::assertSame('new-value', $page->getExtra('new-key'));
        self::assertCount(3, $page->getFiles());
    }

    #[Test]
    public function settingsAreSavedToExistingMarkdownFileWithFrontmatterYaml(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];
        self::assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;

        $settings = $oldPage->getSettings();
        $settings['date'] = '2018-03-14 00:00:00+0000';
        $settings['extra']['new-key'] = 'new-value';
        $newPage->setSettings($settings);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/markdown_only'];

        self::assertSame('2018-03-14', $page->getDate()->format('Y-m-d'));
        self::assertSame('new-value', $page->getExtra('new-key'));
        self::assertCount(1, $page->getFiles());
    }

    #[Test]
    public function settingsAreSavedToDirectoryContainingOnlyTwigFile(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];
        self::assertCount(1, $page->getFiles());

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;

        $settings = $oldPage->getSettings();
        $settings['date'] = '2018-03-14 00:00:00+0000';
        $settings['extra']['new-key'] = 'new-value';
        $newPage->setSettings($settings);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/twig_only'];

        self::assertSame('2018-03-14', $page->getDate()->format('Y-m-d'));
        self::assertSame('new-value', $page->getExtra('new-key'));
        self::assertCount(2, $page->getFiles());
    }

    #[Test]
    public function pageCanBeRenamed(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/with_children'];

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setName('04.still_with_children');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();

        self::assertArrayNotHasKey('/with_children', $pages);
        self::assertArrayHasKey('/still_with_children', $pages);

        $page = $pages['/still_with_children'];
        self::assertCount(2, $page->getChildren());
    }

    #[Test]
    public function pageCanBeMovedToAnotherParent(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $page = $pages['/with_children'];
        $newParent = $pages['/twig_only'];

        $oldPage = $mapper->getWritablePageInstance($page);
        $newPage = clone $oldPage;
        $newPage->setName('moved_but_still_with_children');
        $newPage->setParent($newParent);

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();

        self::assertArrayNotHasKey('/with_children', $pages);

        $parent = $pages['/twig_only'];
        self::assertArrayHasKey('/twig_only/moved_but_still_with_children', $parent->getChildren()->toArray());

        $page = $parent->getChildren()->toArray()['/twig_only/moved_but_still_with_children'];
        self::assertCount(2, $page->getChildren());
    }

    #[Group('new')]
    #[Test]
    public function newPageCanBeSavedInRootDir(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();

        $oldPage = $mapper->getNewWritablePage();
        $newPage = clone $oldPage;
        $newPage->setName('08.totally_new');
        $newPage->setSettings([
            'title' => 'A totally new page!',
            'slug' => 'totally_new',
        ]);
        $newPage->setContentRaw('totally **new** content!');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        self::assertArrayHasKey('/totally_new', $pages);
        $page = $pages['/totally_new'];
        self::assertSame('A totally new page!', $page->getTitle());
        self::assertSame('<p>totally <strong>new</strong> content!</p>', $page->getContent());
    }

    #[Group('new')]
    #[Test]
    public function newPageCanBeSavedInAnotherPageDir(): void
    {
        $mapper = $this->getTempValidPagesFilesystemMapper();
        $pages = $mapper->parse();
        $parent = $pages['/yaml_only'];

        $oldPage = $mapper->getNewWritablePage($parent);
        $newPage = clone $oldPage;
        $newPage->setName('08.totally_new');
        $newPage->setSettings([
            'title' => 'A totally new page!',
            'slug' => 'totally_new',
        ]);
        $newPage->setContentRaw('totally **new** content!');

        $diff = new PageDiff($oldPage, $newPage);

        $mapper->saveChanges($diff);
        $pages = $mapper->parse();
        $page = $pages['/yaml_only'];
        self::assertCount(1, $page->getChildren());
        self::assertArrayHasKey('/yaml_only/totally_new', $page->getChildren()->toArray());
    }

    protected function getTempValidPagesFilesystemMapper(): FilesystemMapper
    {
        $fileFactory = new FileFactory(new FileTypeDetector(), new YamlMetadataLoader(), $this->tempDir);

        return new FilesystemMapper($fileFactory, $this->tempDir, true, [], new NullLogger(), new EventDispatcher());
    }
}
