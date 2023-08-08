<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Service\Api as ApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
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

        return new JsonModel(['healthy' => true]);
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

        $members = $this->apiService->getMembers($additionalProperties);
        $res = ['data' => $members];

        return new JsonModel($res);
    }

    /**
     * Return member
     */
    public function memberAction(): JsonModel|Response
    {
        $this->apiAuthService->assertCan(ApiPermissions::MembersR);

        $member = $this->apiService->getMember(
            (int) $this->params()->fromRoute('id'),
            $this->additionalProperties(),
        );
        if (null === $member) {
            return $this->noContent();
        }

        $res = ['data' => $member];

        return new JsonModel($res);
    }

    /**
     * Return members
     */
    public function membersActiveAction(): JsonModel
    {
        $this->apiAuthService->assertCan(ApiPermissions::MembersActiveR);

        $includeInactiveFraternity = (bool) $this->getRequest()->getQuery('includeInactive', false);

        $members = $this->apiService->getActiveMembers($this->additionalProperties(), $includeInactiveFraternity);
        $res = ['data' => $members];

        return new JsonModel($res);
    }

    /**
     * To follow best practices, we generate a 204 for empty datasets
     */
    private function noContent(): Response
    {
        $response = new Response();
        $response->setStatusCode(Response::STATUS_CODE_204);

        return $response;
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

        return $additionalProperties;
    }
}
