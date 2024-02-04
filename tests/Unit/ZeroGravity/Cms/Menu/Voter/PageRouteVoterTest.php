<?php

namespace Tests\Unit\ZeroGravity\Cms\Menu\Voter;

use Codeception\Attribute\Group;
use InvalidArgumentException;
use Knp\Menu\ItemInterface;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Menu\Voter\PageRouteVoter;

#[Group('voter')]
class PageRouteVoterTest extends BaseUnit
{
    #[Test]
    public function noMatchingIsDoneWithoutRequest(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects(static::never())
            ->method('getExtra')
        ;

        $voter = new PageRouteVoter(new RequestStack());

        self::assertNull($voter->matchItem($item));
    }

    #[Test]
    public function noMatchingIsDoneIfRequestDoesNotContainPage(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects(static::never())
            ->method('getExtra')
        ;

        $stack = new RequestStack();
        $stack->push(new Request());

        $voter = new PageRouteVoter($stack);

        self::assertNull($voter->matchItem($item));
    }

    #[Test]
    public function matchingIsDoneIfRequestContainsPage(): void
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

        self::assertNull($voter->matchItem($item));
    }

    #[Test]
    public function invalidRouteConfigThrowsException(): void
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

    #[Test]
    public function matchingUsingSingleStringRoute(): void
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

        self::assertTrue($voter->matchItem($item));
    }

    #[Test]
    public function matchingUsingRouteArray(): void
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

        self::assertTrue($voter->matchItem($item));
    }

    #[Test]
    public function notMatchingUsingRouteArray(): void
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

        self::assertNull($voter->matchItem($item));
    }
}
