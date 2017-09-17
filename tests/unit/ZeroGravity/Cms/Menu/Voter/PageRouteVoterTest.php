<?php

namespace Tests\Unit\ZeroGravity\Cms\Menu\Voter;

use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Menu\Voter\PageRouteVoter;

/**
 * @group v
 */
class PageRouteVoterTest extends BaseUnit
{
    /**
     * @test
     */
    public function noMatchingIsDoneWithoutRequest()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();
        $item->expects($this->never())
            ->method('getExtra')
        ;

        $voter = new PageRouteVoter();

        $this->assertNull($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function noMatchingIsDoneIfRequestDoesNotContainPage()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();
        $item->expects($this->never())
            ->method('getExtra')
        ;

        $voter = new PageRouteVoter();
        $request = new Request();
        $voter->setRequest($request);

        $this->assertNull($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function matchingIsDoneIfRequestContainsPage()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();
        $item->expects($this->atLeastOnce())
            ->method('getExtra')
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);

        $voter = new PageRouteVoter();
        $voter->setRequest($request);

        $this->assertNull($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function invalidRouteConfigThrowsException()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();
        $item->expects($this->any())
            ->method('getExtra')
            ->with('routes')
            ->will($this->returnValue([['invalid' => 'array']]))
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);

        $voter = new PageRouteVoter();
        $voter->setRequest($request);

        $this->expectException(\InvalidArgumentException::class);
        $voter->matchItem($item);
    }

    /**
     * @test
     */
    public function matchingUsingSingleStringRoute()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();
        $item->expects($this->any())
            ->method('getExtra')
            ->with('routes')
            ->will($this->returnValue('/test'))
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);

        $voter = new PageRouteVoter();
        $voter->setRequest($request);

        $this->assertTrue($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function matchingUsingRouteArray()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();
        $item->expects($this->any())
            ->method('getExtra')
            ->with('routes')
            ->will($this->returnValue([
                ['route' => '/foo'],
                ['route' => '/test'],
            ]))
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);

        $voter = new PageRouteVoter();
        $voter->setRequest($request);

        $this->assertTrue($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function notMatchingUsingRouteArray()
    {
        $item = $this->getMockBuilder(ItemInterface::class)->getMock();
        $item->expects($this->any())
            ->method('getExtra')
            ->with('routes')
            ->will($this->returnValue([
                ['route' => '/foo'],
                ['route' => '/test'],
            ]))
        ;

        $page = new Page('test', ['slug' => 'another-slug'], null);

        $request = new Request();
        $request->attributes->set('page', $page);

        $voter = new PageRouteVoter();
        $voter->setRequest($request);

        $this->assertNull($voter->matchItem($item));
    }
}
