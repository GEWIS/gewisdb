<?php

namespace Checker\Controller;

use Checker\Service\Checker as CheckerService;
use Zend\Mvc\Controller\AbstractActionController;

class CheckerController extends AbstractActionController
{
    /** @var CheckerService $checkerService */
    private $checkerService;

    /**
     * @param CheckerService $checkerService
     */
    public function __construct(CheckerService $checkerService)
    {
        $this->checkerService = $checkerService;
    }

    /**
     * Index action.
     */
    public function indexAction()
    {
        $this->checkerService->check();
    }

    public function checkMembershipsAction()
    {
        $this->checkerService->checkMemberships();
    }

    public function checkDischargesAction()
    {
        $this->checkerService->checkDischarges();
    }
}
