<?php

namespace User\Listener;

use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use User\Adapter\ApiPrincipalAdapter;
use User\Service\ApiAuthenticationService;

final class AuthenticationListener
{
    public function __construct(
        private readonly AuthenticationService $authService,
        private readonly ApiAuthenticationService $apiAuthService,
        private readonly ApiPrincipalAdapter $apiPrincipalAdapter,
    ) {
    }

    public function __invoke(MvcEvent $e)
    {
        if ($this->authService->hasIdentity()) {
            // user is logged in, just continue
            return;
        }

        $match = $e->getRouteMatch();

        if ($match === null) {
            // won't happen, but just in case
            return;
        }

        if (
            $match->getMatchedRouteName() === 'member/default'
            && $match->getParam('action') === 'subscribe'
        ) {
            return;
        }

        if (
            $match->getMatchedRouteName() === 'lang'
            && ($match->getParam('lang') === 'nl' || $match->getParam('lang') === 'en')
        ) {
            return;
        }

        /**
         * Special authorization for API routes
         */
        if (
            $match->getMatchedRouteName() === 'api'
            || str_starts_with($match->getMatchedRouteName(), 'api/')
        ) {
            if ($e->getRequest()->getHeaders()->has('Authorization')) {
                // This is an API call, we do this on every request
                $token = $e->getRequest()->getHeaders()->get('Authorization')->getFieldValue();
                $this->apiPrincipalAdapter->setCredentials(substr($token, strlen("Bearer ")));
                $result = $this->apiAuthService->authenticate($this->apiPrincipalAdapter);
                if ($result->isValid()) {
                    return;
                }
            }

            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer realm="/api"');
            $response->setStatusCode(401);
            return $response;
        }

        if ($match->getMatchedRouteName() !== 'user') {
            $response = $e->getResponse();
            $response->getHeaders()->addHeaderLine('Location', '/user');
            $response->setStatusCode(302);
            return $response;
        }
    }
}
