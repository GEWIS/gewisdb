<?php

namespace Database\Controller;

use Database\Service\{
    InstallationFunction as InstallationFunctionService,
    MailingList as MailingListService,
};
use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SettingsController extends AbstractActionController
{
    public function __construct(
        private readonly InstallationFunctionService $installationFunctionService,
        private readonly MailingListService $mailingListService,
    ) {
    }

    /**
     * Index action.
     */
    public function indexAction(): ViewModel
    {
        return new ViewModel([]);
    }

    /**
     * Function action.
     */
    public function functionAction(): ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $this->installationFunctionService->addFunction($this->getRequest()->getPost()->toArray());
        }

        return new ViewModel([
            'functions' => $this->installationFunctionService->getAllFunctions(),
            'form' => $this->installationFunctionService->getFunctionForm(),
        ]);
    }

    /**
     * Mailing list action
     */
    public function listAction(): ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $this->mailingListService->addList($this->getRequest()->getPost()->toArray());
        }

        return new ViewModel([
            'lists' => $this->mailingListService->getAllLists(),
            'form' => $this->mailingListService->getListForm(),
        ]);
    }

    /**
     * List deletion action
     */
    public function deleteListAction(): Response|ViewModel
    {
        $name = $this->params()->fromRoute('name');

        if ($this->getRequest()->isPost()) {
            if ($this->mailingListService->delete($name, $this->getRequest()->getPost()->toArray())) {
                return new ViewModel([
                    'success' => true,
                    'name' => $name,
                ]);
            } else {
                // redirect back
                return $this->redirect()->toRoute('settings/default', [
                    'action' => 'list',
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->mailingListService->getDeleteListForm(),
            'name' => $name,
        ]);
    }
}
