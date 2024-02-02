<?php

namespace Tests\Unit\ZeroGravity\Cms\Routing;

use Codeception\Stub;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\StructureMapper;
use ZeroGravity\Cms\Routing\RouteProvider;

class RouteProviderTest extends BaseUnit
{
    /**
     * @test
     */
    public function getRouteCollectionReturnsRouteForEachPage(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $collection = $routeProvider->getRouteCollectionForRequest(new Request());
        static::assertSame(3, $collection->count());

        $routes = $collection->all();
        static::assertContainsOnlyInstancesOf(Route::class, $routes);
        foreach ($routes as $route) {
            $page = $route->getDefault('_zg_page');
            static::assertInstanceOf(Page::class, $page);
            static::assertSame('default_controller', $route->getDefault('_controller'));
        }
    }

    /**
     * @test
     */
    public function getRouteByNameReturnsRoute(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $route = $routeProvider->getRouteByName('/page1');
        static::assertInstanceOf(Route::class, $route);
    }

    /**
     * @test
     */
    public function getRouteByNameThrowsExceptionIfNotFound(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $this->expectException(RouteNotFoundException::class);
        $route = $routeProvider->getRouteByName('/invalid/route');
    }

    /**
     * @test
     */
    public function getRoutesByNameReturnsAllRoutesForNull(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $routes = $routeProvider->getRoutesByNames(null);
        static::assertCount(3, $routes);
        static::assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    /**
     * @test
     */
    public function getRoutesByNameReturnsMatchingForArray(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $routes = $routeProvider->getRoutesByNames([
            '/page1',
            '/page2',
            '/invalid/page',
        ]);
        static::assertCount(2, $routes);
        static::assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    private function getContentRepository(): ContentRepository
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

        return new ContentRepository($mapper, $cache, false);
    }

    private function createSimplePage(string $name, Page $parent = null): Page
    {
        return new Page($name, ['slug' => $name], $parent);
    }
}
