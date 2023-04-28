<?php

declare(strict_types=1);

namespace User\Listener;

use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use User\Model\Exception\NotAllowed as NotAllowedException;
use User\Service\ApiAuthenticationService;

final class AuthorizationListener
{
    public function __construct(
        private readonly ApiAuthenticationService $apiAuthService,
    ) {
    }

    public function __invoke(MvcEvent $e): void
    {
        if (
            null === $e->getParam('exception')
            || !($e->getParam('exception') instanceof NotAllowedException)
        ) {
            return;
        }

        $e->stopPropagation(true);
        $e->setViewModel(new JsonModel([
            'status' => 'error',
            'error' => [
                'type' => NotAllowedException::class,
                'message' => $e->getParam('exception')->getMessage(),
            ],
        ]));
        $e->getResponse()->setStatusCode(403);
    }
}
