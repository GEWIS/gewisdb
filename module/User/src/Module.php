<?php

namespace User;

use User\Service\ApiAuthenticationService;
use User\Adapter\ApiPrincipalAdapter;
use User\Mapper\ApiPrincipalMapper;
use Laminas\Authentication\{
    AuthenticationService,
    Result,
};
use Laminas\Mvc\MvcEvent;
use Laminas\Authentication\Storage\Session as SessionStorage;
use Laminas\Http\Request;

class Module
{
    /**
     * On bootstrap.
     */
    public function onBootstrap(MvcEvent $event): void
    {
        $sm = $event->getApplication()->getServiceManager();
        $eventManager = $event->getApplication()->getEventManager();
        $authService = $sm->get(AuthenticationService::class);
        $authService->setStorage(new SessionStorage('gewisdb'));
        $apiAuthService = $sm->get(ApiAuthenticationService::class);
        $authAdapter = $sm->get(ApiPrincipalAdapter::class);

        $eventManager->attach(MvcEvent::EVENT_ROUTE, function (MvcEvent $e) use (
            $authService,
            $apiAuthService,
            $authAdapter,
        ) {
            if ($authService->hasIdentity()) {
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
                    $authAdapter->setCredentials(substr($token, strlen("Bearer ")));
                    $result = $apiAuthService->authenticate($authAdapter);
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
        }, -100);

        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, function ($e) use ($authService) {
            if (!$authService->hasIdentity()) {
                $response = $e->getResponse();
                $response->getHeaders()->addHeaderLine('Location', '/user');
                $response->setStatusCode(302);

                return $response;
            }
        });
    }

    /**
     * Get the configuration for this module.
     *
     * @return array Module configuration
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
