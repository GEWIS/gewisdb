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
}
