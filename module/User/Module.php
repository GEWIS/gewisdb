<?php
namespace User;

use Zend\Authentication\AuthenticationService;
use Zend\Mvc\MvcEvent;

class Module
{

    /**
     * On bootstrap.
     */
    public function onBootstrap($event)
    {
        $sm = $event->getApplication()->getServiceManager();
        $eventManager = $event->getApplication()->getEventManager();
        $authService = $sm->get(AuthenticationService::class);

        $eventManager->attach(MvcEvent::EVENT_ROUTE, function ($e) use ($authService) {
            if ($authService->hasIdentity()) {
                // user is logged in, just continue
                return;
            }

            if ($e->getResponse() instanceof \Zend\Console\Response) {
                // console route, always fine
                return;
            }

            $match = $e->getRouteMatch();

            if ($match === null) {
                // won't happen, but just in case
                return;
            }

            if ($match->getMatchedRouteName() === 'member/default' && $match->getParam('action') === 'subscribe') {
                return;
            }

            if ($match->getMatchedRouteName() === 'lang' && ($match->getParam('lang') === 'nl' || $match->getParam('lang') === 'en')) {
                return;
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
    public function getConfig()
    {
        return include __DIR__ . '/config/module.config.php';
    }
}
