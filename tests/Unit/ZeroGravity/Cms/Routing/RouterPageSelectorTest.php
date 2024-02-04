<?php

namespace Tests\Unit\ZeroGravity\Cms\Routing;

use Codeception\Test\Unit;
use PHPUnit\Framework\Attributes\Test;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Routing\RouterPageSelector;

class RouterPageSelectorTest extends Unit
{
    #[Test]
    public function selectorReturnsNullIfThereIsNoRequest(): void
    {
        $stack = new RequestStack();
        $selector = new RouterPageSelector($stack);

        self::assertNull($selector->getCurrentPage());
    }

    #[Test]
    public function selectorReturnsNullIfCurrentRequestDoesNotContainPageKey(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request([], [], [
            '_route_params' => [],
        ]));
        $selector = new RouterPageSelector($stack);

        self::assertNull($selector->getCurrentPage());
    }

    #[Test]
    public function selectorReturnsNullIfCurrentRequestDoesNotContainPage(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request([], [], [
            '_route_params' => [
                '_zg_page' => 'not-a-page',
            ],
        ]));
        $selector = new RouterPageSelector($stack);

        self::assertNull($selector->getCurrentPage());
    }

    #[Test]
    public function selectorReturnsPageIfCurrentRequestContainsPage(): void
    {
        $stack = new RequestStack();
        $stack->push(new Request([], [], [
            '_route_params' => [
                '_zg_page' => new Page('page'),
            ],
        ]));
        $selector = new RouterPageSelector($stack);

        self::assertInstanceOf(Page::class, $selector->getCurrentPage());
    }
}
