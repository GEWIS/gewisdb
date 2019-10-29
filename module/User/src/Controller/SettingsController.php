<?php

namespace User\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use User\Service\UserService;

class SettingsController extends AbstractActionController
{

    /**
     * @var UserService
     */
    protected $service;

    /**
     * @param UserService $service
     */
    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * View users.
     */
    public function indexAction()
    {
        return new ViewModel([
            'users' => $this->service->findAll()
        ]);
    }

    /**
     * Create a user.
     */
    public function createAction()
    {
        $form = $this->service->getCreateForm();

        if ($this->getRequest()->isPost()) {
            $result = $this->service->create($this->getRequest()->getPost());

            if ($result) {
                return $this->redirect()->toRoute('settings/user');
            }
        }

        return new ViewModel([
            'form' => $form
        ]);
    }

    /**
     * Edit a user.
     */
    public function editAction()
    {
        $form = $this->service->getEditForm();
        $id = $this->params()->fromRoute('id');
        $user = $this->service->find($id);

        if ($this->getRequest()->isPost()) {
            $result = $this->service->edit($user, $this->getRequest()->getPost());

            if ($result) {
                return $this->redirect()->toRoute('settings/user');
            }
        }

        return new ViewModel([
            'form' => $form,
            'user' => $user
        ]);
    }

    /**
     * Remove a user.
     */
    public function removeAction()
    {
        $user = $this->service->find($this->params()->fromRoute('id'));
        if ($this->getRequest()->isPost()) {
            $this->service->remove($user);
        }
        $this->redirect()->toRoute('settings/user');
    }

}
