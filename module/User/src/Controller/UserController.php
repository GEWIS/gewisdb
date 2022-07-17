<?php

namespace User\Controller;

use Laminas\Http\Response;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use User\Service\UserService;

class UserController extends AbstractActionController
{
    protected UserService $service;

    public function __construct(UserService $service)
    {
        $this->service = $service;
    }

    /**
     * User login action
     */
    public function indexAction(): Response|ViewModel
    {
        if ($this->getRequest()->isPost()) {
            $result = $this->service->login($this->getRequest()->getPost()->toArray());

            if ($result) {
                return $this->redirect()->toRoute('home');
            }
        }

        return new ViewModel([
            'form' => $this->service->getLoginForm(),
        ]);
    }

    /**
     * User logout action
     */
    public function logoutAction(): Response
    {
        $this->service->logout();

        return $this->redirect()->toRoute('user');
    }
}
