<?php

declare(strict_types=1);

namespace User\Controller;

use Laminas\Http\Response as HttpResponse;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\Mvc\I18n\Translator as MvcTranslator;
use Laminas\Mvc\Plugin\FlashMessenger\FlashMessenger;
use Laminas\View\Model\ViewModel;
use User\Service\ApiPrincipalService;

use function sprintf;

/**
 * @method FlashMessenger flashMessenger()
 */
class ApiSettingsController extends AbstractActionController
{
    /**
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function __construct(
        protected readonly MvcTranslator $translator,
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
    public function createPrincipalAction(): HttpResponse|ViewModel
    {
        $form = $this->apiPrincipalService->getCreateForm();

        if ($this->getRequest()->isPost()) {
            $result = $this->apiPrincipalService->create($this->getRequest()->getPost()->toArray());

            if (false !== $result) {
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Succesfully created %s!'),
                        $this->translator->translate('API principal'),
                    ),
                );
                $this->flashMessenger()->addInfoMessage(
                    sprintf(
                        $this->translator->translate('Your API token is "%s". This value will NOT be shown again!'),
                        $result->getFullToken(),
                    ),
                );

                return $this->redirectList();
            }
        }

        return new ViewModel(['form' => $form]);
    }

    /**
     * Edit an API principal
     */
    public function editPrincipalAction(): HttpResponse|ViewModel
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
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Change(s) of %s have been saved!'),
                        $this->translator->translate('API principal'),
                    ),
                );

                return $this->redirectList();
            }
        }

        return new ViewModel([
            'form' => $form,
            'principal' => $principal,
        ]);
    }

    public function removePrincipalAction(): HttpResponse
    {
        if ($this->getRequest()->isPost()) {
            $result = $this->apiPrincipalService->remove((int) $this->params()->fromRoute('id'));

            if ($result) {
                $this->flashMessenger()->addSuccessMessage(
                    sprintf(
                        $this->translator->translate('Succesfully deleted %s!'),
                        $this->translator->translate('API principal'),
                    ),
                );
            }
        }

        return $this->redirectList();
    }

    private function redirectList(): HttpResponse
    {
        return $this->redirect()->toRoute('settings/api-principals');
    }

    public function notFoundAction(): HttpResponse
    {
        $this->flashMessenger()->addWarningMessage(
            sprintf(
                $this->translator->translate('Could not find %s!'),
                $this->translator->translate('API principal'),
            ),
        );

        return $this->redirectList();
    }
}
