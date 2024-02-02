<?php

namespace Tests\Unit\ZeroGravity\Cms\Menu\Voter;

use InvalidArgumentException;
use Knp\Menu\ItemInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
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
        $item = $this->createMock(ItemInterface::class);
        $item->expects(static::never())
            ->method('getExtra')
        ;

        $voter = new PageRouteVoter(new RequestStack());

        static::assertNull($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function noMatchingIsDoneIfRequestDoesNotContainPage()
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects(static::never())
            ->method('getExtra')
        ;

        $stack = new RequestStack();
        $stack->push(new Request());
        $voter = new PageRouteVoter($stack);

        static::assertNull($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function matchingIsDoneIfRequestContainsPage()
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects(static::atLeastOnce())
            ->method('getExtra')
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);
        $stack = new RequestStack();
        $stack->push($request);

        $voter = new PageRouteVoter($stack);

        static::assertNull($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function invalidRouteConfigThrowsException()
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->method('getExtra')
            ->with('routes')
            ->willReturn([['invalid' => 'array']])
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);
        $stack = new RequestStack();
        $stack->push($request);

        $voter = new PageRouteVoter($stack);

        $this->expectException(InvalidArgumentException::class);
        $voter->matchItem($item);
    }

    /**
     * @test
     */
    public function matchingUsingSingleStringRoute()
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->method('getExtra')
            ->with('routes')
            ->willReturn('/test')
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);
        $stack = new RequestStack();
        $stack->push($request);

        $voter = new PageRouteVoter($stack);

        static::assertTrue($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function matchingUsingRouteArray()
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->method('getExtra')
            ->with('routes')
            ->willReturn([
                ['route' => '/foo'],
                ['route' => '/test'],
            ])
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = new Request();
        $request->attributes->set('page', $page);
        $stack = new RequestStack();
        $stack->push($request);

        $voter = new PageRouteVoter($stack);

        static::assertTrue($voter->matchItem($item));
    }

    /**
     * @test
     */
    public function notMatchingUsingRouteArray()
    {
        $item = $this->createMock(ItemInterface::class);
        $item
            ->method('getExtra')
            ->with('routes')
            ->willReturn([
                ['route' => '/foo'],
                ['route' => '/test'],
            ])
        ;

        $page = new Page('test', ['slug' => 'another-slug'], null);

        $request = new Request();
        $request->attributes->set('page', $page);
        $stack = new RequestStack();
        $stack->push($request);

        $voter = new PageRouteVoter($stack);

        static::assertNull($voter->matchItem($item));
    }
}
