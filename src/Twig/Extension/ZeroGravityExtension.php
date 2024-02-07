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

final class ZeroGravityExtension extends AbstractExtension
{
    public function __construct(
        private readonly ContentRepository $contentRepository,
        private readonly RouterPageSelector $pageSelector,
        private readonly FilterRegistry $filterRegistry,
    ) {
    }

    /**
     * @return list<TwigFilter>
     */
    public function getFilters(): array
    {
        return [
            new TwigFilter('zg_filter', $this->applyCustomFilter(...)),
            new TwigFilter('zg_page_hash', $this->getPageHash(...)),
            new TwigFilter(
                'zg_render_content',
                $this->renderPageContent(...),
                [
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFilter(
                'zg_render',
                $this->renderPage(...),
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    /**
     * @return list<TwigFunction>
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction('zg_page', $this->getPage(...)),
            new TwigFunction('zg_pages', $this->findPages(...)),
            new TwigFunction('zg_page_hash', $this->getPageHash(...)),
            new TwigFunction('zg_current_page', $this->getCurrentPage(...)),
            new TwigFunction(
                'zg_render_content',
                $this->renderPageContent(...),
                [
                    'is_safe' => ['html'],
                ]
            ),
            new TwigFunction(
                'zg_render',
                $this->renderPage(...),
                [
                    'is_safe' => ['html'],
                    'needs_environment' => true,
                ]
            ),
        ];
    }

    /**
     * Get a single page by path. If the path starts with "./", it will be appended to the current page, thus returning a child page if found.
     */
    public function getPage(string $path): ?ReadablePage
    {
        if (str_starts_with($path, './')) {
            $currentPage = $this->pageSelector->getCurrentPage();

            if ($currentPage instanceof ReadablePage) {
                $path = '/'.ltrim($currentPage->getPath().substr($path, 1), '/');
            }
        }

        return $this->contentRepository->getPage($path);
    }

    /**
     * Fetch the current page instance from the request if available.
     */
    public function getCurrentPage(): ?ReadablePage
    {
        return $this->pageSelector->getCurrentPage();
    }

    /**
     * Apply a filter from the custom filterRegistry to the given page finder.
     *
     * @param array<string, mixed> $filterOptions
     */
    public function applyCustomFilter(PageFinder $pageFinder, string $filterName, array $filterOptions = []): PageFinder
    {
        return $this->filterRegistry->applyFilter($pageFinder, $filterName, $filterOptions);
    }

    /**
     * This can be used to generate an ID attribute-safe representation of a page path.
     */
    public function getPageHash(ReadablePage $page = null): string
    {
        if (!$page instanceof ReadablePage) {
            return 'page_'.md5('');
        }

        return 'page_'.md5($page->getPath()->toString());
    }

    /**
     * Get a PageFinder instance, covering all published pages by default.
     */
    public function findPages(): PageFinder
    {
        return $this->contentRepository->getPageFinder();
    }

    /**
     * @param array<string, mixed> $context
     *
     * @throws LoaderError  When the template cannot be found
     * @throws SyntaxError  When an error occurred during compilation
     * @throws RuntimeError When an error occurred during rendering
     */
    public function renderPage(Environment $environment, Page $page, array $context = []): ?string
    {
        if (null !== $page->getContentTemplate() && '' !== $page->getContentTemplate()) {
            $context['page'] = $page;

            return $environment->render($page->getContentTemplate(), $context);
        }

        return $page->getContent();
    }

    public function renderPageContent(Page $page): ?string
    {
        return $page->getContent();
    }
}
