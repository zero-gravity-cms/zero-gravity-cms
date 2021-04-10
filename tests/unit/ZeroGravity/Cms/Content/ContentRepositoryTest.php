<?php

namespace Tests\Unit\ZeroGravity\Cms\Content;

use Codeception\Util\Stub;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Exception\InvalidArgumentException;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\PageDiff;
use ZeroGravity\Cms\Content\StructureMapper;

class ContentRepositoryTest extends BaseUnit
{
    /**
     * @test
     */
    public function pagesAreLoadedFromMapper()
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

        $this->assertSame([
            $page1,
            $page2,
        ], $repo->getPageTree());

        $this->assertSame([
            '/page1' => $page1,
            '/page2' => $page2,
            '/page2/page3' => $page3,
        ], $repo->getAllPages());

        $this->assertSame($page3, $repo->getPage('/page2/page3'));
        $this->assertNull($repo->getPage('/does/not/exist'));
    }

    /**
     * @test
     */
    public function writablePageIsLoadedFromMapper()
    {
        $page1 = $this->createSimplePage('page1');

        $mapper = $this->getMockBuilder(StructureMapper::class)
            ->setMethods(['parse', 'getWritablePageInstance', 'getNewWritablePage', 'saveChanges'])
            ->getMock()
        ;
        $mapper->expects($this->once())
            ->method('getWritablePageInstance')
        ;

        $repo = new ContentRepository($mapper, new ArrayAdapter(), false);
        $repo->getWritablePageInstance($page1);
    }

    /**
     * @test
     */
    public function newWritablePageIsLoadedFromMapper()
    {
        $page1 = $this->createSimplePage('page1');

        $mapper = $this->getMockBuilder(StructureMapper::class)
            ->setMethods(['parse', 'getWritablePageInstance', 'getNewWritablePage', 'saveChanges'])
            ->getMock()
        ;
        $mapper->expects($this->once())
            ->method('getNewWritablePage')
            ->with($page1)
        ;

        $repo = new ContentRepository($mapper, new ArrayAdapter(), false);
        $repo->getNewWritablePage($page1);
    }

    /**
     * @test
     */
    public function diffIsSavedThroughMapper()
    {
        $page1 = $this->createSimplePage('page1');

        $mapper = $this->getMockBuilder(StructureMapper::class)
            ->setMethods(['parse', 'getWritablePageInstance', 'getNewWritablePage', 'saveChanges'])
            ->getMock()
        ;

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

    /**
     * @test
     */
    public function pagesAreCachedBetweenInstances()
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

        $this->assertEquals([
            '/page1' => $page1,
            '/page2' => $page2,
            '/page2/page3' => $page3,
        ], $repo2->getAllPages(), 'The parser is never called if pages reside in the cache.');

        $repo3 = new ContentRepository($emptyMapper, $cache, false);
        $repo3->clearCache();
        $this->assertEquals([], $repo3->getAllPages(), 'After clearing the cache the empty result is loaded.');
    }

    /**
     * @test
     */
    public function pagesAreNotCachedIfSkipCacheIsSet()
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

        $this->assertEquals([], $repo2->getAllPages());
    }

    /**
     * @test
     */
    public function pagesAreLoadedIfCacheThrowsException()
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
            ->setMethods(['getItem'])
            ->getMock()
        ;
        $cache->expects(self::atLeast(1))
            ->method('getItem')
            ->willThrowException(new InvalidArgumentException())
        ;

        $repo = new ContentRepository($mapper, $cache, false);
        $repo->getAllPages();
    }

    /**
     * @param $name
     *
     * @return Page
     */
    private function createSimplePage($name, Page $parent = null)
    {
        return new Page($name, ['slug' => $name], $parent);
    }
}
