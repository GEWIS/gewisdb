<?php

namespace Api\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Api\Service\ApiKey as ApiKeyService;

class AdminController extends AbstractActionController
{

    /**
     * @var ApiKeyService
     */
    protected $service;

    public function __construct(ApiKeyService $service)
    {
        $this->service = $service;
    }

    public function indexAction()
    {
        return new ViewModel([
            'keys' => $this->service->findAll()
        ]);
    }

    public function createAction()
    {
        if ($this->getRequest()->isPost()) {
            $result = $this->service->create($this->getRequest()->getPost());
            if ($result !== null) {
                return $this->redirect()->toRoute('settings/api');
            }
        }

        return new ViewModel([
            'form' => $this->service->getApiKeyForm()
        ]);
    }

    public function deleteAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('settings/api');
        }
        $id = $this->params()->fromRoute('id');

        $this->service->delete($id);

        return $this->redirect()->toRoute('settings/api');
    }
}
