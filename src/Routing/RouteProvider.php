<?php

namespace ZeroGravity\Cms\Routing;

use Symfony\Cmf\Component\Routing\RouteProviderInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Exception\RouteNotFoundException;
use Symfony\Component\Routing\Route;
use Symfony\Component\Routing\RouteCollection;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Content\ReadablePageRepository;

final class RouteProvider implements RouteProviderInterface
{
    private ReadablePageRepository $repository;
    private string $defaultController;

    public function __construct(ReadablePageRepository $repository, string $defaultController)
    {
        $this->repository = $repository;
        $this->defaultController = $defaultController;
    }

    /**
     * Finds routes that may potentially match the request.
     *
     * This may return a mixed list of class instances, but all routes returned
     * must extend the core symfony route. The classes may also implement
     * RouteObjectInterface to link to a content document.
     *
     * This method may not throw an exception based on implementation specific
     * restrictions on the url. That case is considered a not found - returning
     * an empty array. Exceptions are only used to abort the whole request in
     * case something is seriously broken, like the storage backend being down.
     *
     * Note that implementations may not implement an optimal matching
     * algorithm, simply a reasonable first pass.  That allows for potentially
     * very large route sets to be filtered down to likely candidates, which
     * may then be filtered in memory more completely.
     *
     * @param Request $request A request against which to match
     *
     * @return RouteCollection with all Routes that could potentially match
     *                         $request. Empty collection if nothing can match
     */
    public function getRouteCollectionForRequest(Request $request): RouteCollection
    {
        $collection = new RouteCollection();

        foreach ($this->getRoutesByNames(null) as $route) {
            $collection->add('zerogravity_'.uniqid('', true), $route);
        }

        return $collection;
    }

    /**
     * Find the route using the provided route name.
     *
     * @param string $name The route name to fetch
     *
     * @throws RouteNotFoundException If there is no route with that name in
     *                                this repository
     */
    public function getRouteByName($name): Route
    {
        $page = $this->repository->getPage($name);
        if (null === $page) {
            throw new RouteNotFoundException(sprintf('Cannot find zerogravity page route with name %s', $name));
        }

        return $this->createRouteFromPage($page);
    }

    /**
     * Find many routes by their names using the provided list of names.
     *
     * Note that this method may not throw an exception if some of the routes
     * are not found or are not actually Route instances. It will just return the
     * list of those Route instances it found.
     *
     * This method exists in order to allow performance optimizations. The
     * simple implementation could be to just repeatedly call
     * $this->getRouteByName() while catching and ignoring eventual exceptions.
     *
     * If $names is null, this method SHOULD return a collection of all routes
     * known to this provider. If there are many routes to be expected, usage of
     * a lazy loading collection is recommended. A provider MAY only return a
     * subset of routes to e.g. support paging or other concepts, but be aware
     * that the DynamicRouter will only call this method once per
     * DynamicRouter::getRouteCollection() call.
     *
     * @param array|null $names The list of names to retrieve, In case of null,
     *                          the provider will determine what routes to return
     *
     * @return Route[] Iterable list with the keys being the names from the
     *                 $names array
     */
    public function getRoutesByNames($names): array
    {
        $pages = $this->repository->getAllPages();
        if (is_array($names)) {
            $pages = array_intersect_key($pages, array_flip($names));
        }

        $routes = [];
        foreach ($pages as $page) {
            foreach ($this->extractPageRoutes($page) as $route) {
                $routes[] = $route;
            }
        }

        return $routes;
    }

    private function extractPageRoutes(Page $page): array
    {
        $routes = [
            new Route($page->getPath()->toString(), [
                '_zg_page' => $page,
                '_controller' => $page->getController() ?: $this->defaultController,
            ]),
        ];
        foreach ($page->getDocuments() as $name => $file) {
            $routes[] = new Route($page->getPath()->toString().'/'.$name, [
                '_zg_page' => $page,
                '_controller' => $page->getController() ?: $this->defaultController,
                '_zg_file' => $file,
            ]);
        }
        foreach ($page->getImages() as $name => $file) {
            $routes[] = new Route($page->getPath()->toString().'/'.$name, [
                '_zg_page' => $page,
                '_controller' => $page->getController() ?: $this->defaultController,
                '_zg_file' => $file,
            ]);
        }

        return $routes;
    }

    public function createRouteFromPage(ReadablePage $page): Route
    {
        return new Route($page->getPath()->toString(), [
            '_zg_page' => $page,
            '_controller' => $page->getController() ?: $this->defaultController,
        ]);
    }
}
