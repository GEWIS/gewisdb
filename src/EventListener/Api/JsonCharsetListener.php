<?php

declare(strict_types=1);

namespace App\EventListener\Api;

use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

use function str_starts_with;

/**
 * Keeps `charset=utf-8` on the API's JSON responses.
 *
 * Consumers have been reading `application/json; charset=utf-8` since the API existed, and a client that matches the
 * header exactly would stop recognising the response without it. Symfony's JsonResponse states the media type alone,
 * which says the same thing — JSON is always UTF-8 — but not in the same bytes.
 */
#[AsEventListener(event: KernelEvents::RESPONSE)]
final readonly class JsonCharsetListener
{
    private const string API_PREFIX = '/api';
    private const string JSON = 'application/json';

    public function __invoke(ResponseEvent $event): void
    {
        if (
            !str_starts_with(
                $event->getRequest()->getPathInfo(),
                self::API_PREFIX,
            )
        ) {
            return;
        }

        $response = $event->getResponse();

        if (self::JSON !== $response->headers->get('Content-Type')) {
            return;
        }

        $response->headers->set(
            'Content-Type',
            self::JSON . '; charset=utf-8',
        );
    }
}
