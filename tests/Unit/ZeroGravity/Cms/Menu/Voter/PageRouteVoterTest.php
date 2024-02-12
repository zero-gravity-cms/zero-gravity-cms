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
use ZeroGravity\Cms\Routing\RouterPageSelector;

#[Group('voter')]
class PageRouteVoterTest extends BaseUnit
{
    #[Test]
    public function noMatchingIsDoneWithoutRequest(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->never())
            ->method('getExtra')
        ;

        $voter = $this->buildVoter();

        self::assertNull($voter->matchItem($item));
    }

    #[Test]
    public function noMatchingIsDoneIfRequestDoesNotContainPage(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->never())
            ->method('getExtra')
        ;

        $voter = $this->buildVoter(new Request());

        self::assertNull($voter->matchItem($item));
    }

    #[Test]
    public function matchingIsDoneIfRequestContainsPage(): void
    {
        $item = $this->createMock(ItemInterface::class);
        $item->expects($this->atLeastOnce())
            ->method('getExtra')
        ;

        $page = new Page('test', ['slug' => 'test'], null);

        $request = $this->buildPageRequest($page);
        $voter = $this->buildVoter($request);

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

        $request = $this->buildPageRequest($page);
        $voter = $this->buildVoter($request);

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

        $request = $this->buildPageRequest($page);
        $voter = $this->buildVoter($request);

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

        $request = $this->buildPageRequest($page);
        $voter = $this->buildVoter($request);

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

        $request = $this->buildPageRequest($page);
        $voter = $this->buildVoter($request);

        self::assertNull($voter->matchItem($item));
    }

    private function buildVoter(Request $request = null): PageRouteVoter
    {
        $stack = new RequestStack();
        if ($request instanceof Request) {
            $stack->push($request);
        }

        return new PageRouteVoter(new RouterPageSelector($stack));
    }

    private function buildPageRequest(Page $page): Request
    {
        $request = new Request();
        $request->attributes->set('_route_params', ['_zg_page' => $page]);

        return $request;
    }
}
