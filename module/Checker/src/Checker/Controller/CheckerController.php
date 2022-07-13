<?php

namespace Checker\Controller;

use Checker\Service\Checker as CheckerService;
use Zend\Mvc\Controller\AbstractActionController;

class CheckerController extends AbstractActionController
{
    /**
     * Index action.
     */
    public function indexAction()
    {
        /** @var CheckerService $service */
        $service = $this->getServiceLocator()->get('checker_service_checker');
        $service->check();
    }

    public function checkMembershipsAction()
    {
        /** @var CheckerService $service */
        $service = $this->getServiceLocator()->get('checker_service_checker');
        $service->checkMemberships();
    }

    public function checkDischargesAction()
    {
        /** @var CheckerService $service */
        $service = $this->getServiceLocator()->get('checker_service_checker');
        $service->checkDischarges();
    }
}
