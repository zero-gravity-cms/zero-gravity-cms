<?php

namespace Tests\Unit\ZeroGravity\Cms\Twig\Extension;

use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use Tests\Unit\ZeroGravity\Cms\Test\TwigExtensionTestTrait;
use Twig\Loader\FilesystemLoader;
use Twig\Loader\LoaderInterface;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Finder\FilterRegistry;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Routing\RouterPageSelector;
use ZeroGravity\Cms\Twig\Extension\ZeroGravityExtension;

/**
 * @group twig
 */
class ZeroGravityExtensionTest extends BaseUnit
{
    use TwigExtensionTestTrait;

    public function getExtensions()
    {
        $mapper = $this->getValidPagesFilesystemMapper();
        $repository = new ContentRepository($mapper, new ArrayAdapter(), false);

        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [
            '_route_params' => [
                '_zg_page' => $repository->getPage('/with_children'),
            ],
        ]));

        $routePageSelector = new RouterPageSelector($requestStack);

        $filterRegistry = new FilterRegistry();
        $filterRegistry->addFilter(
            'filter-custom-children',
            fn (PageFinder $pageFinder, array $filterOptions) => $pageFinder->contentType('custom-child')
        );
        $filterRegistry->addFilter(
            'sort-path-desc',
            fn (PageFinder $pageFinder, array $filterOptions) => $pageFinder->sort(fn (Page $a, Page $b) => $b->getPath() <=> $a->getPath())
        );

        return [
            new ZeroGravityExtension($repository, $routePageSelector, $filterRegistry),
        ];
    }

    /**
     * Get the directory containing the twig fixture data.
     *
     * Unfortunately the helper module is not available inside data providers,
     * so we have to hard-code the path here.
     *
     * @return string
     */
    protected function getFixturesDir()
    {
        return __DIR__.'/../../../../../_data/twig_fixtures';
    }

    /**
     * @return LoaderInterface[]
     */
    protected function getTwigLoaders()
    {
        $filesystemLoader = new FilesystemLoader();
        $filesystemLoader->addPath($this->getValidPagesDir(), 'ZeroGravity');

        return [
            $filesystemLoader,
        ];
    }
}
