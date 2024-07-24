<?php

declare(strict_types=1);

namespace User;

use Laminas\Authentication\AuthenticationService;
use Laminas\Authentication\Storage\NonPersistent as NonPersistentStorage;
use Laminas\Authentication\Storage\Session as SessionStorage;
use Laminas\Mvc\MvcEvent;
use User\Adapter\ApiPrincipalAdapter;
use User\Listener\AuthenticationListener;
use User\Listener\AuthorizationListener;
use User\Listener\DispatchErrorFormatterListener;
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
        $authorizationListener = new AuthorizationListener();
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, $authorizationListener, 10);

        /**
         * Format errors in case of dispatch errors after authentication
         */
        $dispatchErrorFormatterListener = new DispatchErrorFormatterListener();
        $eventManager->attach(MvcEvent::EVENT_DISPATCH_ERROR, $dispatchErrorFormatterListener, 5);
    }

    /**
     * Get the configuration for this module.
     */
    public function getConfig(): array
    {
        return include __DIR__ . '/../config/module.config.php';
    }
}
