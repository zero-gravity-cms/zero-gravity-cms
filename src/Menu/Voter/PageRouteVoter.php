<?php

namespace ZeroGravity\Cms\Menu\Voter;

use InvalidArgumentException;
use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use ZeroGravity\Cms\Content\ReadablePage;

final readonly class PageRouteVoter implements VoterInterface
{
    public function __construct(
        private RequestStack $requestStack,
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
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }
        $page = $request->attributes->get('page');
        if (!$page instanceof ReadablePage) {
            return null;
        }

        $routes = (array) $item->getExtra('routes', []);

        return $this->matchRoutes($routes, $page->getPath()->toString());
    }

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
