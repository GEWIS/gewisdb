<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\ApiPrincipalService;

class ApiSettingsController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        protected readonly ApiPrincipalService $apiPrincipalService,
        protected readonly array $config,
    ) {
    }

    /**
     * View users.
     */
    public function listPrincipalsAction(): ViewModel
    {
        return new ViewModel([
            'principals' => $this->apiPrincipalService->findAll(),
        ]);
    }
}
