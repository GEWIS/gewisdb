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
            $result = $this->apiPrincipalService->create(
                $this->getRequest()->getPost()->toArray(),
                $this->flashMessenger(),
            );

            if ($result) {
                $this->flashMessenger()->addSuccessMessage('Succesfully created API principal');

                return $this->redirectList();
            }
        }

        return new ViewModel(['form' => $form]);
    }

    /**
     * Edit an API principal
     */
    public function editPrincipalAction(): Response|ViewModel
    {
        $id = (int) $this->params()->fromRoute('id');
        $principal = $this->apiPrincipalService->find($id);

        if (null === $principal) {
            return $this->notFoundAction();
        }

        $form = $this->apiPrincipalService->getEditForm($principal);

        if ($this->getRequest()->isPost()) {
            $result = $this->apiPrincipalService->edit($principal, $this->getRequest()->getPost()->toArray());

            if ($result) {
                $this->flashMessenger()->addSuccessMessage('Succesfully updated API principal');

                return $this->redirectList();
            }
        }

        return new ViewModel([
            'form' => $form,
            'principal' => $principal,
        ]);
    }

    public function removePrincipalAction(): Response
    {
        if ($this->getRequest()->isPost()) {
            $result = $this->apiPrincipalService->remove((int) $this->params()->fromRoute('id'));

            if ($result) {
                $this->flashMessenger()->addSuccessMessage('Succesfully removed API principal');
            }
        }

        return $this->redirectList();
    }

    private function redirectList(): Response
    {
        return $this->redirect()->toRoute('settings/api-principals');
    }

    public function notFoundAction(): Response
    {
        $this->flashMessenger()->addWarningMessage('Could not find requested API principal');

        return $this->redirectList();
    }
}
