<?php

declare(strict_types=1);

namespace Database\Controller;

use Database\Model\Enums\ApiResponseStatuses;
use Database\Model\Enums\InstallationFunctions as OrganInstallationFunctions;
use Database\Service\Api as ApiService;
use Laminas\Http\Request as HttpRequest;
use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator;
use Laminas\View\Model\JsonModel;
use PHLAK\SemVer\Version as SemanticVersion;
use RuntimeException;
use User\Model\Enums\ApiPermissions;
use User\Service\ApiAuthenticationService;

use function array_diff;

class ApiController extends AbstractActionController
{
    public function __construct(
        private readonly Translator $translator,
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
    public function memberAction(): JsonModel
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
     * Return organ functions
     */
    public function organFunctionsAction(): JsonModel
    {
        $this->apiAuthService->assertCan(ApiPermissions::OrganFunctionsListR);
        $this->assertVersions('v4.3.3', null, $this->getRequest());

        $functions = OrganInstallationFunctions::getMultilangArray($this->translator);

        $res = [
            'status' => ApiResponseStatuses::Success,
            'data' => $functions,
        ];

        return new JsonModel($res);
    }

    /**
     * To follow best practices, we generate a 204 for empty datasets
     */
    private function noContent(): JsonModel
    {
        $response = $this->getResponse();
        if ($response instanceof HttpResponse) {
            $response->setStatusCode(HttpResponse::STATUS_CODE_204);
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

    private function assertVersions(
        string $minimumVersion,
        ?string $maximumVersion,
        HttpRequest $request,
    ): void {
        $minimumVersion = new SemanticVersion($minimumVersion);

        if (null !== $maximumVersion) {
            $maximumVersion = new SemanticVersion($maximumVersion);
        }

        $acceptHeader = $request->getHeaders('Accept', null);
        $this->apiService->assertVersion($minimumVersion, $maximumVersion, $acceptHeader);
    }
}
