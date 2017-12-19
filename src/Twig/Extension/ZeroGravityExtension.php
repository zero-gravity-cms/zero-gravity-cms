<?php

namespace ZeroGravity\Cms\Twig\Extension;

use Twig_Environment;
use Twig_Extension;
use Twig_SimpleFilter;
use Twig_SimpleFunction;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Finder\FilterRegistry;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Routing\RouterPageSelector;

class ZeroGravityExtension extends Twig_Extension
{
    /**
     * @var ContentRepository
     */
    private $contentRepository;

    /**
     * @var RouterPageSelector
     */
    private $pageSelector;

    /**
     * @var FilterRegistry
     */
    private $filterRegistry;

    public function __construct(ContentRepository $contentRepository, RouterPageSelector $pageSelector, FilterRegistry $filterRegistry)
    {
        $this->contentRepository = $contentRepository;
        $this->pageSelector = $pageSelector;
        $this->filterRegistry = $filterRegistry;
    }

    public function getFilters()
    {
        return [
            new Twig_SimpleFilter('zg_filter', [$this, 'filterPages']),
            new Twig_SimpleFilter(
                'zg_render_content',
                [$this, 'renderPageContent'],
                [
                    'is_safe' => ['html'],
                ]
            ),
            new Twig_SimpleFilter(
                'zg_render',
                [$this, 'renderPage'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    public function getFunctions()
    {
        return [
            new Twig_SimpleFunction('zg_page', [$this, 'getPage']),
            new Twig_SimpleFunction('zg_current_page', [$this, 'getCurrentPage']),
            new Twig_SimpleFunction('zg_filter', [$this, 'filterAllPages']),
            new Twig_SimpleFunction(
                'zg_render_content',
                [$this, 'renderPageContent'],
                [
                    'is_safe' => ['html'],
                ]
            ),
            new Twig_SimpleFunction(
                'zg_render',
                [$this, 'renderPage'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    /**
     * @param string $path
     *
     * @return null|Page
     */
    public function getPage(string $path): ? Page
    {
        if (0 === strpos($path, './')) {
            $currentPage = $this->pageSelector->getCurrentPage();

            if (null !== $currentPage) {
                $path = '/'.ltrim($currentPage->getPath().substr($path, 1), '/');
            }
        }

        return $this->contentRepository->getPage($path);
    }

    /**
     * @return null|Page
     */
    public function getCurrentPage(): ? Page
    {
        return $this->pageSelector->getCurrentPage();
    }

    /**
     * @param PageFinder $pageFinder
     * @param string     $filterName
     * @param array      $filterOptions
     *
     * @return PageFinder
     */
    public function filterPages(PageFinder $pageFinder, string $filterName, array $filterOptions = []): PageFinder
    {
        $pageFinder = $this->filterRegistry->applyFilter($pageFinder, $filterName, $filterOptions);

        return $pageFinder;
    }

    /**
     * @param string $filterName
     * @param array  $filterOptions
     *
     * @return PageFinder
     */
    public function filterAllPages(string $filterName, array $filterOptions = []): PageFinder
    {
        $pageFinder = $this->contentRepository->getPageFinder();

        return $this->filterPages($pageFinder, $filterName, $filterOptions);
    }

    /**
     * @param Twig_Environment $environment
     * @param Page             $page
     * @param array            $context
     *
     * @return null|string
     *
     * @throws \Twig_Error_Loader
     * @throws \Twig_Error_Runtime
     * @throws \Twig_Error_Syntax
     */
    public function renderPage(Twig_Environment $environment, Page $page, array $context = [])
    {
        if (!empty($page->getContentTemplate())) {
            $context['page'] = $page;

            return $environment->render($page->getContentTemplate(), $context);
        }

        return $page->getContent();
    }

    /**
     * @param Page $page
     *
     * @return null|string
     */
    public function renderPageContent(Page $page)
    {
        return $page->getContent();
    }
}
