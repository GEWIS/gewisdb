<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Service\Api as ApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use User\Model\Enums\ApiPermissions;
use User\Service\ApiAuthenticationService;

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

        $members = $this->apiService->getMembers();
        $res = ['data' => $members];

        return new JsonModel($res);
    }

    /**
     * Return members
     */
    public function memberAction(): JsonModel|Response
    {
        $this->apiAuthService->assertCan(ApiPermissions::MembersR);

        $member = $this->apiService->getMember((int) $this->params()->fromRoute('id'));
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

        $includeOrganMembership = $this->apiAuthService->currentUserCan(ApiPermissions::OrgansMembershipR);
        $members = $this->apiService->getActiveMembers($includeOrganMembership);
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
}
