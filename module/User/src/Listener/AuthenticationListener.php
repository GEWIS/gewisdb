<?php

namespace User\Listener;

use InvalidArgumentException;
use Laminas\Authentication\AuthenticationService;
use Laminas\Mvc\MvcEvent;
use Laminas\Stdlib\ResponseInterface as Response;
use LogicException;
use User\Adapter\ApiPrincipalAdapter;
use User\Service\ApiAuthenticationService;

final class AuthenticationListener
{
    // Defining the authentication types
    public const AUTH_NONE = 'none';
    public const AUTH_DBUSER = 'dbuser';
    public const AUTH_API = 'api';

    public function __construct(
        private readonly AuthenticationService $authService,
        private readonly ApiAuthenticationService $apiAuthService,
        private readonly ApiPrincipalAdapter $apiPrincipalAdapter,
    ) {
    }

    public function __invoke(MvcEvent $e)
    {
        if ($e->getName() !== MvcEvent::EVENT_ROUTE) {
            throw new InvalidArgumentException(
                "Expected MvcEvent of type " . MvcEvent::EVENT_ROUTE . ", got " . $e->getName()
            );
        }

        $match = $e->getRouteMatch();
        if ($match === null) {
            throw new LogicException("Did not match any route after being routed");
        }

        switch ($match->getParam('auth_type', self::AUTH_DBUSER)) {
            case self::AUTH_DBUSER:
                return $this->dbuserAuth($e);
            case self::AUTH_API:
                return $this->apiAuth($e);
            case self::AUTH_NONE:
                return;
            default:
                throw new InvalidArgumentException(
                    "Authentication type was set to unknown type " . $match->getParam('auth_type')
                );
        }
    }

    /**
     * Handle authentication for users
     */
    private function dbuserAuth(MvcEvent $e): ?Response
    {
        if ($this->authService->hasIdentity()) {
            // user is logged in, just continue
            return null;
        }

        $e->stopPropagation(true);
        $response = $e->getResponse();
        $response->getHeaders()->addHeaderLine('Location', '/user');
        $response->setStatusCode(302);
        return $response;
    }

    /**
     * Handle authentication for api tokens
     */
    private function apiAuth(MvcEvent $e): ?Response
    {
        if ($e->getRequest()->getHeaders()->has('Authorization')) {
            // This is an API call, we do this on every request
            $token = $e->getRequest()->getHeaders()->get('Authorization')->getFieldValue();
            $this->apiPrincipalAdapter->setCredentials(substr($token, strlen("Bearer ")));
            $result = $this->apiAuthService->authenticate($this->apiPrincipalAdapter);
            if ($result->isValid()) {
                return null;
            }
        }

        $response = $e->getResponse();
        $response->getHeaders()->addHeaderLine('WWW-Authenticate', 'Bearer realm="/api"');
        $response->setStatusCode(401);
        return $response;
    }
}
