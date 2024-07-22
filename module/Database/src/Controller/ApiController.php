<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Model\Enums\ApiResponseStatuses;
use Database\Service\Api as ApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Stdlib\ResponseInterface;
use Laminas\View\Model\JsonModel;
use RuntimeException;
use User\Model\Enums\ApiPermissions;
use User\Service\ApiAuthenticationService;

use function array_diff;

class ApiController extends AbstractActionController
{
    public function __construct(
        private readonly ApiService $apiService,
        private readonly ApiAuthenticationService $apiAuthService,
    ) {
    }

    /**
     * Healthy action.
     */
    public function healthyAction(): JsonModel
    {
        $this->apiAuthService->assertCan(ApiPermissions::HealthR);

        $syncPaused = $this->apiService->isSyncPaused();

        return new JsonModel([
            'status' => ApiResponseStatuses::Success,
            'healthy' => true,
            'sync_paused' => $syncPaused,
        ]);
    }

    /**
     * Error 500 action.
     */
    public function example500Action(): JsonModel
    {
        throw new RuntimeException('An example exception was thrown.');
    }

    /**
     * Return members
     */
    public function membersAction(): JsonModel
    {
        $this->apiAuthService->assertCan(ApiPermissions::MembersR);

        $additionalProperties = $this->additionalProperties();
        if (!$this->getRequest()->getQuery('includeOrgans', false)) {
            $additionalProperties = array_diff($additionalProperties, ['organs']);
        }

        $allowDeleted = $this->apiAuthService->currentUserCan(ApiPermissions::MembersDeleted);

        $members = $this->apiService->getMembers($additionalProperties, $allowDeleted);
        $res = [
            'status' => ApiResponseStatuses::Success,
            'data' => $members,
        ];

        return new JsonModel($res);
    }

    /**
     * Return member
     */
    public function memberAction(): JsonModel|ResponseInterface
    {
        $this->apiAuthService->assertCan(ApiPermissions::MembersR);

        $allowDeleted = $this->apiAuthService->currentUserCan(ApiPermissions::MembersDeleted);
        $member = $this->apiService->getMember(
            (int) $this->params()->fromRoute('id'),
            $this->additionalProperties(),
            $allowDeleted,
        );
        if (null === $member) {
            return $this->noContent();
        }

        $res = [
            'status' => ApiResponseStatuses::Success,
            'data' => $member,
        ];

        return new JsonModel($res);
    }

    /**
     * Return members
     */
    public function membersActiveAction(): JsonModel
    {
        $this->apiAuthService->assertCan(ApiPermissions::MembersActiveR);

        $includeInactiveFraternity = (bool) $this->getRequest()->getQuery('includeInactive', false);
        $allowDeleted = $this->apiAuthService->currentUserCan(ApiPermissions::MembersDeleted);

        $members = $this->apiService->getActiveMembers(
            $this->additionalProperties(),
            $includeInactiveFraternity,
            $allowDeleted,
        );
        $res = [
            'status' => ApiResponseStatuses::Success,
            'data' => $members,
        ];

        return new JsonModel($res);
    }

    /**
     * To follow best practices, we generate a 204 for empty datasets
     */
    private function noContent(): JsonModel
    {
        $response = $this->getResponse();
        if ($response instanceof Response) {
            $response->setStatusCode(Response::STATUS_CODE_204);
        }

        $res = [
            'status' => ApiResponseStatuses::Success,
            'data' => null,
        ];

        return new JsonModel($res);
    }

    /**
     * @return array<array-key,string> $additionalProperties
     */
    private function additionalProperties(): array
    {
        $additionalProperties = [];
        if ($this->apiAuthService->currentUserCan(ApiPermissions::OrgansMembershipR)) {
            $additionalProperties[] = 'organs';
        }

        if ($this->apiAuthService->currentUserCan(ApiPermissions::MembersPropertyKeyholder)) {
            $additionalProperties[] = 'keyholder';
        }

        if ($this->apiAuthService->currentUserCan(ApiPermissions::MembersPropertyType)) {
            $additionalProperties[] = 'type';
        }

        if ($this->apiAuthService->currentUserCan(ApiPermissions::MembersPropertyEmail)) {
            $additionalProperties[] = 'email';
        }

        if ($this->apiAuthService->currentUserCan(ApiPermissions::MembersPropertyBirthDate)) {
            $additionalProperties[] = 'birthdate';
        }

        if ($this->apiAuthService->currentUserCan(ApiPermissions::MembersPropertyAge16)) {
            $additionalProperties[] = 'is_16_plus';
        }

        if ($this->apiAuthService->currentUserCan(ApiPermissions::MembersPropertyAge18)) {
            $additionalProperties[] = 'is_18_plus';
        }

        if ($this->apiAuthService->currentUserCan(ApiPermissions::MembersPropertyAge21)) {
            $additionalProperties[] = 'is_21_plus';
        }

        return $additionalProperties;
    }
}
