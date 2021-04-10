<?php

namespace Tests\Unit\ZeroGravity\Cms\Routing;

use Codeception\Test\Unit;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Routing\RouterPageSelector;

class RouterPageSelectorTest extends Unit
{
    /**
     * @test
     */
    public function selectorReturnsNullIfThereIsNoRequest()
    {
        $stack = new RequestStack();
        $selector = new RouterPageSelector($stack);

        $this->assertNull($selector->getCurrentPage());
    }

    /**
     * @test
     */
    public function selectorReturnsNullIfCurrentRequestDoesNotContainPageKey()
    {
        $stack = new RequestStack();
        $stack->push(new Request([], [], [
            '_route_params' => [],
        ]));
        $selector = new RouterPageSelector($stack);

        $this->assertNull($selector->getCurrentPage());
    }

    /**
     * @test
     */
    public function selectorReturnsNullIfCurrentRequestDoesNotContainPage()
    {
        $stack = new RequestStack();
        $stack->push(new Request([], [], [
            '_route_params' => [
                '_zg_page' => 'not-a-page',
            ],
        ]));
        $selector = new RouterPageSelector($stack);

        $this->assertNull($selector->getCurrentPage());
    }

    /**
     * @test
     */
    public function selectorReturnsPageIfCurrentRequestContainsPage()
    {
        $stack = new RequestStack();
        $stack->push(new Request([], [], [
            '_route_params' => [
                '_zg_page' => new Page('page'),
            ],
        ]));
        $selector = new RouterPageSelector($stack);

        $this->assertInstanceOf(Page::class, $selector->getCurrentPage());
    }
}
