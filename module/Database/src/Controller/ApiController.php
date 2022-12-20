<?php

namespace Database\Controller;

use Application\Model\Enums\AddressTypes;
use Checker\Service\Checker as CheckerService;
use Database\Service\Api as ApiService;
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class ApiController extends AbstractActionController
{
    public function __construct(
        private readonly ApiService $apiService,
    ) {
    }

    /**
     * Healthy action.
     */
    public function healthyAction(): JsonModel
    {
        return new JsonModel([
            "healthy" => true,
        ]);
    }

    /**
     * Return members
     */
    public function membersAction(): JsonModel
    {
        $members = $this->apiService->getMembers();
        $res = [
            "data" => $members,
        ];
        return new JsonModel($res);
    }

    /**
     * Return members
     */
    public function memberAction(): JsonModel
    {
        $member = $this->apiService->getMember((int) $this->params()->fromRoute('id'));
        $res = [
            "data" => $member,
        ];
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
