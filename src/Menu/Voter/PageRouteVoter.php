<?php

namespace ZeroGravity\Cms\Menu\Voter;

use InvalidArgumentException;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use ZeroGravity\Cms\Content\ReadablePage;
use ZeroGravity\Cms\Routing\RouterPageSelector;

/**
 * This voter checks for routes provided by the CMF RouteProvider. These will include
 * page details that can be extracted by the RouterPageSelector.
 */
final readonly class PageRouteVoter implements VoterInterface
{
    public function __construct(
        private RouterPageSelector $pageSelector,
    ) {
    }

    /**
     * Checks whether an item is current.
     *
     * If the voter is not able to determine a result,
     * it should return null to let other voters do the job.
     */
    public function matchItem(ItemInterface $item): ?bool
    {
        $page = $this->pageSelector->getCurrentPage();
        if (!$page instanceof ReadablePage) {
            return null;
        }

        $routes = (array) $item->getExtra('routes', []);

        return $this->matchRoutes($routes, $page->getPath()->toString());
    }

    /**
     * @param array<mixed> $routes
     */
    private function matchRoutes(array $routes, string $pagePath): ?bool
    {
        foreach ($routes as $route) {
            if (is_string($route)) {
                $route = ['route' => $route];
            }
            if (!is_array($route) || !isset($route['route'])) {
                throw new InvalidArgumentException('Routes extra items must be strings or arrays with route key.');
            }

            if ($pagePath === $route['route']) {
                return true;
            }
        }

        return null;
    }
}
