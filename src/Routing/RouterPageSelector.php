<?php

namespace ZeroGravity\Cms\Routing;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use ZeroGravity\Cms\Content\ReadablePage;

/**
 * Determine current page based on routing.
 */
final readonly class RouterPageSelector
{
    public function __construct(
        private RequestStack $requestStack,
    ) {
    }

    public function getCurrentPage(): ?ReadablePage
    {
        $request = $this->requestStack->getCurrentRequest();
        if (!$request instanceof Request) {
            return null;
        }

        $params = $request->attributes->get('_route_params');
        if (!isset($params['_zg_page'])) {
            return null;
        }
        if (!$params['_zg_page'] instanceof ReadablePage) {
            return null;
        }

        return $params['_zg_page'];
    }
}
