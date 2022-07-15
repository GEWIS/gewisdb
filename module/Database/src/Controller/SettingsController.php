<?php

namespace Database\Controller;

use Database\Model\Member;
use Database\Service\InstallationFunction as InstallationFunctionService;
use Database\Service\MailingList as MailingListService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class SettingsController extends AbstractActionController
{
    /** @var InstallationFunctionService $installationFunctionService */
    private $installationFunctionService;

    /** @var MailingListService $mailingListService */
    private $mailingListService;

    public function __construct(
        InstallationFunctionService $installationFunctionService,
        MailingListService $mailingListService,
    ) {
        $this->installationFunctionService = $installationFunctionService;
        $this->mailingListService = $mailingListService;
    }

    /**
     * Index action.
     */
    public function indexAction()
    {
        return new ViewModel([]);
    }

    /**
     * Function action.
     */
    public function functionAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->installationFunctionService->addFunction($this->getRequest()->getPost());
        }

        return new ViewModel([
            'functions' => $this->installationFunctionService->getAllFunctions(),
            'form' => $this->installationFunctionService->getFunctionForm(),
        ]);
    }

    /**
     * Mailing list action
     */
    public function listAction()
    {
        if ($this->getRequest()->isPost()) {
            $this->mailingListService->addList($this->getRequest()->getPost());
        }

        return new ViewModel([
            'lists' => $this->mailingListService->getAllLists(),
            'form' => $this->mailingListService->getListForm(),
        ]);
    }

    /**
     * List deletion action
     */
    public function deleteListAction()
    {
        $name = $this->params()->fromRoute('name');

        if ($this->getRequest()->isPost()) {
            if ($this->mailingListService->delete($name, $this->getRequest()->getPost())) {
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
