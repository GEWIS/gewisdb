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
        $this->getMeetingService()->generate();

        echo "generating organ tables\n";
        $this->getOrganService()->generate();
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
     * Get the meeting service.
     *
     * @return Report\Service\Meeting
     */
    public function getMeetingService()
    {
        return $this->getServiceLocator()->get('report_service_meeting');
    }

    /**
     * Get the organ service.
     *
     * @return Report\Service\Organ
     */
    public function getOrganService()
    {
        return $this->getServiceLocator()->get('report_service_organ');
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
