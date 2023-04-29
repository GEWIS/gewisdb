<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Service\ApiPrincipalService;

/**
 * @method FlashMessenger flashMessenger()
 */
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
     * View API principals.
     */
    public function listPrincipalsAction(): ViewModel
    {
        return new ViewModel([
            'principals' => $this->apiPrincipalService->findAll(),
        ]);
    }

    /**
     * Create an API principal
     */
    public function createPrincipalAction(): Response|ViewModel
    {
        $form = $this->apiPrincipalService->getCreateForm();

        if ($this->getRequest()->isPost()) {
            $result = $this->apiPrincipalService->create($this->getRequest()->getPost()->toArray());

            if ($result) {
                $this->flashMessenger()->addSuccessMessage('Succesfully created API principal');

                return $this->redirect()->toRoute('settings/api-principals');
            }
        }

        return new ViewModel(['form' => $form]);
    }
}
