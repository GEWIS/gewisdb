<?php

declare(strict_types=1);

namespace User\Listener;

use Database\Model\Enums\ApiResponseStatuses;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\MvcEvent;
use Laminas\View\Model\JsonModel;
use User\Model\Exception\NotAllowed as NotAllowedException;

final class AuthorizationListener
{
    public function __invoke(MvcEvent $e): void
    {
        if (
            null === $e->getParam('exception')
            || !($e->getParam('exception') instanceof NotAllowedException)
        ) {
            return;
        }

        $e->setViewModel(new JsonModel([
            'status' => ApiResponseStatuses::Error,
            'error' => [
                'type' => NotAllowedException::class,
                'message' => $e->getParam('exception')->getMessage(),
            ],
        ]));
        $response = $e->getResponse();
        if ($response instanceof HttpResponse) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_403);
        }

        $e->stopPropagation();
    }
}
