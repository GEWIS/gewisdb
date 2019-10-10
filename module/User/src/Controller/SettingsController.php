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
        return new ViewModel([]);
    }

    /**
     * Create a user.
     */
    public function createAction()
    {
        $form = $this->service->getCreateForm();

        return new ViewModel([
            'form' => $form
        ]);
    }
}
