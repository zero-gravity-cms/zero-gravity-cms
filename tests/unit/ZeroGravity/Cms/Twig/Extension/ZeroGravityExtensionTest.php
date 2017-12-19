<?php

namespace Tests\Unit\ZeroGravity\Cms\Twig\Extension;

use Symfony\Component\Cache\Simple\ArrayCache;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Tests\Unit\ZeroGravity\Cms\Test\BaseUnit;
use Tests\Unit\ZeroGravity\Cms\Test\TwigExtensionTestTrait;
use Twig_Loader_Filesystem;
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
        $parser = $this->getValidPagesFilesystemParser();
        $repository = new ContentRepository($parser, new ArrayCache(), false);

        $requestStack = new RequestStack();
        $requestStack->push(new Request([], [], [
            '_route_params' => [
                'page' => $repository->getPage('/with_children'),
            ],
        ]));

        $routePageSelector = new RouterPageSelector($requestStack);

        $filterRegistry = new FilterRegistry();
        $filterRegistry->addFilter('filter-custom-children', function (PageFinder $pageFinder, array $filterOptions) {
            return $pageFinder
                ->contentType('custom-child')
            ;
        });
        $filterRegistry->addFilter('sort-path-desc', function (PageFinder $pageFinder, array $filterOptions) {
            return $pageFinder
                ->sort(function (Page $a, Page $b) {
                    return $b->getPath() <=> $a->getPath();
                })
            ;
        });

        return array(
            new ZeroGravityExtension($repository, $routePageSelector, $filterRegistry),
        );
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
     * @return Twig_LoaderInterface[]
     */
    protected function getTwigLoaders()
    {
        $filesystemLoader = new Twig_Loader_Filesystem();
        $filesystemLoader->addPath($this->getValidPagesDir(), 'ZeroGravity');

        return [
            $filesystemLoader,
        ];
    }
}
