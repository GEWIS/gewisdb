<?php

namespace User;

use Laminas\Authentication\{
    AuthenticationService,
    Result,
};
use Laminas\Mvc\MvcEvent;
use Laminas\Authentication\Storage\Session as SessionStorage;
use Laminas\Authentication\Storage\NonPersistent as NonPersistentStorage;
use Laminas\Http\Request;
use User\Adapter\ApiPrincipalAdapter;
use User\Listener\{
    AuthenticationListener,
    AuthorizationListener
};
use User\Mapper\ApiPrincipalMapper;
use User\Service\ApiAuthenticationService;

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
        $apiAuthService->setStorage(new NonPersistentStorage());
        $apiPrincipalAdapter = $sm->get(ApiPrincipalAdapter::class);

        /**
         * Establish an identity of the user using the authentication listener
         */
        $authenticationListener = new AuthenticationListener(
            $authService,
            $apiAuthService,
            $apiPrincipalAdapter,
        );
        $eventManager->attach(MvcEvent::EVENT_ROUTE, $authenticationListener, -100);

        /**
         * Catch authorization exceptions
         */
        $authorizationListener = new AuthorizationListener(
            $apiAuthService,
        );
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, $authorizationListener);

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
