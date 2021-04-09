<?php

namespace ZeroGravity\Cms\Menu\Voter;

use Knp\Menu\ItemInterface;
use Knp\Menu\Matcher\Voter\VoterInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use ZeroGravity\Cms\Content\Page;

class PageRouteVoter implements VoterInterface
{
    /**
     * @var Request
     */
    private $request;

    public function __construct(RequestStack $requestStack)
    {
        $this->request = $requestStack->getCurrentRequest();
    }

    /**
     * Checks whether an item is current.
     *
     * If the voter is not able to determine a result,
     * it should return null to let other voters do the job.
     *
     * @return bool|null
     */
    public function matchItem(ItemInterface $item)
    {
        if (null === $this->request) {
            return null;
        }
        $page = $this->request->attributes->get('page');
        if (!$page instanceof Page) {
            return null;
        }

        $routes = (array) $item->getExtra('routes', []);

        return $this->matchRoutes($routes, $page->getPath()->toString());
    }

    /**
     * @return bool|null
     */
    private function matchRoutes(array $routes, string $pagePath)
    {
        foreach ($routes as $route) {
            if (is_string($route)) {
                $route = ['route' => $route];
            }
            if (!is_array($route) || !isset($route['route'])) {
                throw new \InvalidArgumentException('Routes extra items must be strings or arrays with route key.');
            }

            if ($pagePath === $route['route']) {
                return true;
            }
        }

        return null;
    }
}
