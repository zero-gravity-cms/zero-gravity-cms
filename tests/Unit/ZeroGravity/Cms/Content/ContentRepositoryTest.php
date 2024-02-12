<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Codeception\Stub;
use PHPUnit\Framework\Attributes\DoesNotPerformAssertions;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\StructureMapper;

class ContentRepositoryTest extends BaseUnit
{
    #[Test]
    public function pagesAreLoadedFromMapper(): void
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');
        $page3 = $this->createSimplePage('page3', $page2);

        $mapper = Stub::makeEmpty(StructureMapper::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);
        $repo = new ContentRepository($mapper, new ArrayAdapter(), false);

        self::assertSame([
            $page1,
            $page2,
        ], $repo->getPageTree());

        self::assertSame([
            '/page1' => $page1,
            '/page2' => $page2,
            '/page2/page3' => $page3,
        ], $repo->getAllPages());

        self::assertSame($page3, $repo->getPage('/page2/page3'));
        self::assertNull($repo->getPage('/does/not/exist'));
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function writablePageIsLoadedFromMapper(): void
    {
        $page1 = $this->createSimplePage('page1');

        $mapper = $this->createMock(StructureMapper::class);
        $mapper->expects($this->once())
            ->method('getWritablePageInstance')
        ;

        $repo = new ContentRepository($mapper, new ArrayAdapter(), false);
        $repo->getWritablePageInstance($page1);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function newWritablePageIsLoadedFromMapper(): void
    {
        $page1 = $this->createSimplePage('page1');

        $mapper = $this->createMock(StructureMapper::class);
        $mapper->expects($this->once())
            ->method('getNewWritablePage')
            ->with($page1)
        ;

        $repo = new ContentRepository($mapper, new ArrayAdapter(), false);
        $repo->getNewWritablePage($page1);
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function diffIsSavedThroughMapper(): void
    {
        $page1 = $this->createSimplePage('page1');

        $mapper = $this->createMock(StructureMapper::class);

        $repo = new ContentRepository($mapper, new ArrayAdapter(), false);
        $old = $repo->getWritablePageInstance($page1);
        $new = clone $old;
        $diff = new PageDiff($old, $new);

        $mapper->expects($this->once())
            ->method('saveChanges')
            ->with($diff)
        ;

        $repo->saveChanges($diff);
    }

    #[Test]
    public function pagesAreCachedBetweenInstances(): void
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');
        $page3 = $this->createSimplePage('page3', $page2);

        $mapper = Stub::makeEmpty(StructureMapper::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);
        $cache = new ArrayAdapter();
        $repo = new ContentRepository($mapper, $cache, false);
        $repo->getAllPages();

        $emptyMapper = Stub::makeEmpty(StructureMapper::class, [
            'parse' => [],
        ]);
        $repo2 = new ContentRepository($emptyMapper, $cache, false);

        self::assertEquals([
            '/page1' => $page1,
            '/page2' => $page2,
            '/page2/page3' => $page3,
        ], $repo2->getAllPages(), 'The parser is never called if pages reside in the cache.');

        $repo3 = new ContentRepository($emptyMapper, $cache, false);
        $repo3->clearCache();
        self::assertSame([], $repo3->getAllPages(), 'After clearing the cache the empty result is loaded.');
    }

    #[Test]
    public function pagesAreNotCachedIfSkipCacheIsSet(): void
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');

        $mapper = Stub::makeEmpty(StructureMapper::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);
        $cache = new ArrayAdapter();
        $repo = new ContentRepository($mapper, $cache, false);
        $repo->getAllPages();

        $emptyMapper = Stub::makeEmpty(StructureMapper::class, [
            'parse' => [],
        ]);
        $repo2 = new ContentRepository($emptyMapper, $cache, true);

        self::assertSame([], $repo2->getAllPages());
    }

    #[Test]
    #[DoesNotPerformAssertions]
    public function pagesAreLoadedIfCacheThrowsException(): void
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');

        $mapper = Stub::makeEmpty(StructureMapper::class, [
            'parse' => [
                $page1,
                $page2,
            ],
        ]);

        $cache = $this->getMockBuilder(ArrayAdapter::class)
            ->onlyMethods(['getItem'])
            ->getMock()
        ;
        $cache->expects($this->atLeast(1))
            ->method('getItem')
            ->willThrowException(new InvalidArgumentException())
        ;

        $repo = new ContentRepository($mapper, $cache, false);
        $repo->getAllPages();
    }

    private function createSimplePage(string $name, Page $parent = null): Page
    {
        return new Page($name, ['slug' => $name], $parent);
    }
}
