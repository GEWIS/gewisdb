<?php

namespace Checker\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class CheckerController extends AbstractActionController
{
    /**
     * Index action.
     */
    public function indexAction()
    {
        $service = $this->getServiceLocator()->get('checker_service_checker');
        $service->check();
    }

    public function checkMembershipsAction()
    {
        $service = $this->getServiceLocator()->get('checker_service_checker');
        $service->checkMemberships();
    }
}
