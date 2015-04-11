<?php

namespace Report\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class ReportController extends AbstractActionController
{

    /**
     * Generate reporting database.
     */
    public function generateAction()
    {
        $console = $this->getConsole();

        echo "generating members table\n";
        $this->getMemberService()->generate();

        echo "generating meetings and decision tables\n";
    }

    /**
     * Get the member service.
     *
     * @return Report\Service\Member
     */
    public function getMemberService()
    {
        return $this->getServiceLocator()->get('report_service_member');
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
