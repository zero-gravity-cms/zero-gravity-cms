<?php

namespace ZeroGravity\Cms\Twig\Extension;

use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;
use ZeroGravity\Cms\Content\ContentRepository;
use ZeroGravity\Cms\Content\Finder\FilterRegistry;
use ZeroGravity\Cms\Content\Finder\PageFinder;
use ZeroGravity\Cms\Content\Page;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Routing\RouterPageSelector;

class ZeroGravityExtension extends AbstractExtension
{
    private ContentRepository $contentRepository;

    private RouterPageSelector $pageSelector;

    private FilterRegistry $filterRegistry;

    public function __construct(ContentRepository $contentRepository, RouterPageSelector $pageSelector, FilterRegistry $filterRegistry)
    {
        $this->contentRepository = $contentRepository;
        $this->pageSelector = $pageSelector;
        $this->filterRegistry = $filterRegistry;
    }

    public function getFilters()
    {
        return [
            new TwigFilter('zg_filter', [$this, 'filterPages']),
            new TwigFilter('zg_page_hash', [$this, 'getPageHash']),
            new TwigFilter(
                'zg_render_content',
                [$this, 'renderPageContent'],
                [
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFilter(
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
            new TwigFunction('zg_page', [$this, 'getPage']),
            new TwigFunction('zg_page_hash', [$this, 'getPageHash']),
            new TwigFunction('zg_current_page', [$this, 'getCurrentPage']),
            new TwigFunction('zg_filter', [$this, 'filterAllPages']),
            new TwigFunction(
                'zg_render_content',
                [$this, 'renderPageContent'],
                [
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'zg_render',
                [$this, 'renderPage'],
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    public function getPage(string $path): ?Page
    {
        if (0 === strpos($path, './')) {
            $currentPage = $this->pageSelector->getCurrentPage();

            if (null !== $currentPage) {
                $path = '/'.ltrim($currentPage->getPath().substr($path, 1), '/');
            }
        }

        return $this->contentRepository->getPage($path);
    }

    public function getCurrentPage(): ?Page
    {
        return $this->pageSelector->getCurrentPage();
    }

    public function filterPages(PageFinder $pageFinder, string $filterName, array $filterOptions = []): PageFinder
    {
        $pageFinder = $this->filterRegistry->applyFilter($pageFinder, $filterName, $filterOptions);

        return $pageFinder;
    }

    /**
     * @param ReadablePage $page
     */
    public function getPageHash(ReadablePage $page = null): string
    {
        if (null === $page) {
            return 'page_'.md5('');
        }

        return 'page_'.md5($page->getPath()->toString());
    }

    public function filterAllPages(string $filterName, array $filterOptions = []): PageFinder
    {
        $pageFinder = $this->contentRepository->getPageFinder();

        return $this->filterPages($pageFinder, $filterName, $filterOptions);
    }

    /**
     * @throws LoaderError  When the template cannot be found
     * @throws SyntaxError  When an error occurred during compilation
     * @throws RuntimeError When an error occurred during rendering
     */
    public function renderPage(Environment $environment, Page $page, array $context = []): ?string
    {
        if (!empty($page->getContentTemplate())) {
            $context['page'] = $page;

            return $environment->render($page->getContentTemplate(), $context);
        }

        return $page->getContent();
    }

    /**
     * @return string|null
     */
    public function renderPageContent(Page $page)
    {
        return $page->getContent();
    }
}
