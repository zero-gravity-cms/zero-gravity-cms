<?php

namespace ZeroGravity\Cms\Routing;

use Symfony\Component\HttpFoundation\RequestStack;
use ZeroGravity\Cms\Content\Page;

/**
 * Determine current page based on routing.
 */
final class RouterPageSelector
{
    private RequestStack $requestStack;

    public function __construct(RequestStack $requestStack)
    {
        $this->requestStack = $requestStack;
    }

    public function getCurrentPage(): ?Page
    {
        $request = $this->requestStack->getCurrentRequest();
        if (null === $request) {
            return null;
        }

        $params = $request->attributes->get('_route_params');
        if (!isset($params['_zg_page'])) {
            return null;
        }
        if (!$params['_zg_page'] instanceof Page) {
            return null;
        }

        return $params['_zg_page'];
    }
}
