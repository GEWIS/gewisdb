<?php

declare(strict_types=1);

namespace App\Security\Api;

use App\Entity\Application\Enums\ApiResponseStatuses;
use App\Entity\User\Enums\ApiPermissions;
use App\Exception\Database\AnnulmentNotPossible;
use App\Exception\Report\VersionExpected;
use App\Exception\Report\VersionFormat;
use App\Exception\Report\VersionIncompatible;
use App\Exception\User\ApiException;
use App\Exception\User\NotAllowed;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\Routing\Exception\ResourceNotFoundException;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Throwable;

use function is_string;
use function str_starts_with;

/**
 * Renders failures below `/api` as the JSON envelope other GEWIS applications parse:
 * `{"status": "...", "error": {"type": "...", "exception": "..."}}`.
 *
 * Two listeners with different priorities, because the two cases have different neighbours:
 *  - an authorization failure has to be shaped before the firewall's own exception listener (priority 1) turns it
 *    into a bare 403, but a request that never authenticated is left to that listener so it answers with the
 *    bearer challenge instead;
 *  - everything else is shaped after the framework has logged the exception (priority 0) and before the default
 *    error renderer (priority -128) would produce an HTML page.
 */
#[AsEventListener(
    event: KernelEvents::EXCEPTION,
    method: 'onAccessDenied',
    priority: 2,
)]
#[AsEventListener(
    event: KernelEvents::EXCEPTION,
    method: 'onApiException',
    priority: -100,
)]
final class ApiExceptionListener
{
    private const string API_PREFIX = '/api';

    /** A request that matched no route at all. */
    private const string TYPE_NO_ROUTE = 'error-router-no-match';

    /**
     * How the API names each failure it can report.
     *
     * The body has always carried the exception's class name, so consumers key on strings naming classes that no
     * longer exist. Those strings are part of the contract and outlived the classes, which is why they are stated
     * here rather than derived from `::class` — and why they live at the wire boundary rather than on the
     * exceptions, which have no business knowing their JSON name.
     */
    private const array TYPES = [
        NotAllowed::class => 'User\\Model\\Exception\\NotAllowed',
        VersionExpected::class => 'Database\\Model\\Exception\\VersionExpected',
        VersionFormat::class => 'Database\\Model\\Exception\\VersionFormat',
        VersionIncompatible::class => 'Database\\Model\\Exception\\VersionIncompatible',
        AnnulmentNotPossible::class => 'Database\\Model\\Exception\\AnnulmentNotPossible',
    ];

    public function __construct(private readonly TokenStorageInterface $tokenStorage)
    {
    }

    public function onAccessDenied(ExceptionEvent $event): void
    {
        if (!$this->isApiRequest($event->getRequest())) {
            return;
        }

        $accessDenied = $this->findAccessDenied($event->getThrowable());

        if (null === $accessDenied) {
            return;
        }

        // Without an authenticated principal this is a missing credential rather than a missing permission.
        if (!($this->tokenStorage->getToken() instanceof ApiToken)) {
            return;
        }

        $permission = $this->deniedPermission($accessDenied);
        $message = null === $permission
            ? $accessDenied->getMessage()
            : new NotAllowed($permission)->getMessage();

        $this->respond(
            $event,
            Response::HTTP_FORBIDDEN,
            self::TYPES[NotAllowed::class],
            $message,
        );
    }

    public function onApiException(ExceptionEvent $event): void
    {
        if (!$this->isApiRequest($event->getRequest())) {
            return;
        }

        $throwable = $event->getThrowable();
        $type = $this->type($throwable);

        $this->respond(
            $event,
            $this->statusCode($throwable),
            $type,
            // A routing failure says nothing a consumer can act on, and the framework's message repeats the URL back.
            self::TYPE_NO_ROUTE === $type ? null : $throwable->getMessage(),
            $throwable instanceof HttpExceptionInterface ? $throwable->getHeaders() : [],
        );
    }

    private function isApiRequest(Request $request): bool
    {
        $path = $request->getPathInfo();

        return self::API_PREFIX === $path
            || str_starts_with(
                $path,
                self::API_PREFIX . '/',
            );
    }

    private function findAccessDenied(Throwable $throwable): ?AccessDeniedException
    {
        $current = $throwable;

        while (null !== $current) {
            if ($current instanceof AccessDeniedException) {
                return $current;
            }

            $current = $current->getPrevious();
        }

        return null;
    }

    /**
     * The permission that was voted on, so the response can name it the way `NotAllowed` always has.
     */
    private function deniedPermission(AccessDeniedException $exception): ?ApiPermissions
    {
        foreach ($exception->getAttributes() as $attribute) {
            if ($attribute instanceof ApiPermissions) {
                return $attribute;
            }

            if (!is_string($attribute)) {
                continue;
            }

            $permission = ApiPermissions::tryFrom($attribute);

            if (null !== $permission) {
                return $permission;
            }
        }

        return null;
    }

    private function statusCode(Throwable $throwable): int
    {
        return match (true) {
            // `NotAllowed` inherits the 500 default of `ApiException`, but an authorization failure is a 403.
            $throwable instanceof NotAllowed,
            $throwable instanceof AccessDeniedException => Response::HTTP_FORBIDDEN,
            $throwable instanceof ApiException => $throwable->getHttpStatusCode(),
            $throwable instanceof HttpExceptionInterface => $throwable->getStatusCode(),
            default => Response::HTTP_INTERNAL_SERVER_ERROR,
        };
    }

    private function type(Throwable $throwable): string
    {
        if (
            $throwable instanceof NotFoundHttpException
            && $throwable->getPrevious() instanceof ResourceNotFoundException
        ) {
            return self::TYPE_NO_ROUTE;
        }

        if ($throwable instanceof AccessDeniedException) {
            return self::TYPES[NotAllowed::class];
        }

        return self::TYPES[$throwable::class] ?? $throwable::class;
    }

    private function responseStatus(int $statusCode): ApiResponseStatuses
    {
        return match ($statusCode) {
            Response::HTTP_FORBIDDEN => ApiResponseStatuses::Forbidden,
            Response::HTTP_NOT_FOUND => ApiResponseStatuses::NotFound,
            default => ApiResponseStatuses::Error,
        };
    }

    /**
     * @param array<string, string> $headers
     */
    private function respond(
        ExceptionEvent $event,
        int $statusCode,
        string $type,
        ?string $message,
        array $headers = [],
    ): void {
        $event->setResponse(new JsonResponse(
            [
                'status' => $this->responseStatus($statusCode)->value,
                'error' => [
                    'type' => $type,
                    'exception' => $message,
                ],
            ],
            $statusCode,
            $headers,
        ));
    }
}
