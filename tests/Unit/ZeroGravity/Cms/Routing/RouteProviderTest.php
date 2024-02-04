<?php

namespace Tests\Unit\ZeroGravity\Cms\Routing;

use Codeception\Stub;
use PHPUnit\Framework\Attributes\Test;
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
    #[Test]
    public function getRouteCollectionReturnsRouteForEachPage(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $collection = $routeProvider->getRouteCollectionForRequest(new Request());
        self::assertSame(3, $collection->count());

        $routes = $collection->all();
        self::assertContainsOnlyInstancesOf(Route::class, $routes);
        foreach ($routes as $route) {
            $page = $route->getDefault('_zg_page');
            self::assertInstanceOf(Page::class, $page);
            self::assertSame('default_controller', $route->getDefault('_controller'));
        }
    }

    #[Test]
    public function getRouteByNameReturnsRoute(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $route = $routeProvider->getRouteByName('/page1');
        self::assertInstanceOf(Route::class, $route);
    }

    #[Test]
    public function getRouteByNameThrowsExceptionIfNotFound(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $this->expectException(RouteNotFoundException::class);
        $routeProvider->getRouteByName('/invalid/route');
    }

    #[Test]
    public function getRoutesByNameReturnsAllRoutesForNull(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $routes = $routeProvider->getRoutesByNames(null);
        self::assertCount(3, $routes);
        self::assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    #[Test]
    public function getRoutesByNameReturnsMatchingForArray(): void
    {
        $routeProvider = new RouteProvider($this->getContentRepository(), 'default_controller');

        $routes = $routeProvider->getRoutesByNames([
            '/page1',
            '/page2',
            '/invalid/page',
        ]);
        self::assertCount(2, $routes);
        self::assertContainsOnlyInstancesOf(Route::class, $routes);
    }

    private function getContentRepository(): ContentRepository
    {
        $page1 = $this->createSimplePage('page1');
        $page2 = $this->createSimplePage('page2');
        $this->createSimplePage('page3', $page2);

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
