<?php

declare(strict_types=1);

namespace App\Controller\Report;

use App\Entity\Application\Enums\ApiResponseStatuses;
use App\Entity\User\Enums\ApiPermissions;
use App\Service\Report\ApiService;
use RuntimeException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * The envelope, the status codes and the version negotiation of these endpoints are a contract with the other GEWIS
 * applications that read them.
 */
#[Route(path: '/api')]
final class ApiController extends AbstractController
{
    public function __construct(private readonly ApiService $apiService)
    {
    }

    #[Route(
        path: '',
        name: 'api_index',
        methods: ['GET'],
    )]
    #[Route(
        path: '/health',
        name: 'api_health',
        methods: ['GET'],
    )]
    #[IsGranted(ApiPermissions::HealthR->value)]
    public function health(): JsonResponse
    {
        return new JsonResponse([
            'status' => ApiResponseStatuses::Success->value,
            'healthy' => true,
            'sync_paused' => $this->apiService->isSyncPaused(),
        ]);
    }

    /**
     * Lets a consumer confirm that it handles a failing endpoint the way it intends to.
     */
    #[Route(
        path: '/example500',
        name: 'api_example500',
        methods: ['GET'],
    )]
    public function example500(): never
    {
        throw new RuntimeException('An example exception was thrown.');
    }

    #[Route(
        path: '/members',
        name: 'api_members',
        methods: ['GET'],
    )]
    #[IsGranted(ApiPermissions::MembersR->value)]
    public function members(Request $request): JsonResponse
    {
        return new JsonResponse([
            'status' => ApiResponseStatuses::Success->value,
            'data' => $this->apiService->getMembers((bool) $request->query->get('includeOrgans', false)),
        ]);
    }

    #[Route(
        path: '/members/active',
        name: 'api_members_active',
        methods: ['GET'],
    )]
    #[IsGranted(ApiPermissions::MembersActiveR->value)]
    public function membersActive(Request $request): JsonResponse
    {
        return new JsonResponse([
            'status' => ApiResponseStatuses::Success->value,
            'data' => $this->apiService->getActiveMembers((bool) $request->query->get('includeInactive', false)),
        ]);
    }

    #[Route(
        path: '/members/{id}',
        name: 'api_member',
        requirements: ['id' => '\d+'],
        methods: ['GET'],
    )]
    #[IsGranted(ApiPermissions::MembersR->value)]
    public function member(int $id): JsonResponse
    {
        $member = $this->apiService->getMember($id);

        // An unknown or invisible member is an empty dataset rather than a missing resource, which is a 204.
        return new JsonResponse(
            [
                'status' => ApiResponseStatuses::Success->value,
                'data' => $member,
            ],
            null === $member ? Response::HTTP_NO_CONTENT : Response::HTTP_OK,
        );
    }

    #[Route(
        path: '/organFunctions',
        name: 'api_organ_functions',
        methods: ['GET'],
    )]
    #[IsGranted(ApiPermissions::OrganFunctionsListR->value)]
    public function organFunctions(Request $request): JsonResponse
    {
        return new JsonResponse([
            'status' => ApiResponseStatuses::Success->value,
            'data' => $this->apiService->getOrganFunctions($request->headers->get('Accept')),
        ]);
    }

    #[Route(
        path: '/boardFunctions',
        name: 'api_board_functions',
        methods: ['GET'],
    )]
    #[IsGranted(ApiPermissions::BoardFunctionsListR->value)]
    public function boardFunctions(Request $request): JsonResponse
    {
        return new JsonResponse([
            'status' => ApiResponseStatuses::Success->value,
            'data' => $this->apiService->getBoardFunctions($request->headers->get('Accept')),
        ]);
    }
}
