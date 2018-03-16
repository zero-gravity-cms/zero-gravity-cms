<?php

namespace Tests\Unit\ZeroGravity\Cms\Routing;

use Codeception\Util\Stub;
use Symfony\Component\Cache\Simple\ArrayCache;
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
    public function getRouteCollectionReturnsRouteForEachPage()
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $collection = $routeProvider->getRouteCollectionForRequest(new Request());
        $this->assertSame(3, $collection->count());

        $routes = $collection->all();
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);
        foreach ($routes as $route) {
            $page = $route->getDefault('page');
            $this->assertInstanceOf(Page::class, $page);
            $this->assertSame('default_controller', $route->getDefault('_controller'));
        }
    }

    /**
     * @test
     */
    public function getRouteByNameReturnsRoute()
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $route = $routeProvider->getRouteByName('/page1');
        $this->assertInstanceOf(Route::class, $route);
    }

    /**
     * @test
     */
    public function getRouteByNameThrowsExceptionIfNotFound()
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $this->expectException(RouteNotFoundException::class);
        $route = $routeProvider->getRouteByName('/invalid/route');
    }

    /**
     * @test
     */
    public function getRoutesByNameReturnsAllRoutesForNull()
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $routes = $routeProvider->getRoutesByNames(null);
        $this->assertCount(3, $routes);
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    /**
     * @test
     */
    public function getRoutesByNameReturnsMatchingForArray()
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $routes = $routeProvider->getRoutesByNames([
            '/page1',
            '/page2',
            '/invalid/page',
        ]);
        $this->assertCount(2, $routes);
        $this->assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    /**
     * @return ContentRepository
     */
    private function getContentRepository()
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
        $cache = new ArrayCache();

        return new ContentRepository($mapper, $cache, false);
    }

    /**
     * @param           $name
     * @param Page|null $parent
     *
     * @return Page
     */
    private function createSimplePage($name, Page $parent = null)
    {
        $page = new Page($name, ['slug' => $name], $parent);

        return $page;
    }
}
