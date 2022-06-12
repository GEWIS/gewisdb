<?php

namespace Export\Controller;

use Zend\Mvc\Controller\AbstractActionController;

class ExportController extends AbstractActionController
{
    /**
     * Export to the old database.
     *
     * Old action.
     */
    public function oldAction()
    {
        $console = $this->getConsole();

        $this->getMemberService()->export();
        $this->getOrganService()->export();
        $this->getMeetingService()->export();
    }

    /**
     * Get the meeting service.
     *
     * @return Export\Service\Meeting
     */
    public function getMeetingService()
    {
        return $this->getServiceLocator()->get('export_service_meeting');
    }

    /**
     * Get the member service.
     *
     * @return Export\Service\Member
     */
    public function getMemberService()
    {
        return $this->getServiceLocator()->get('export_service_member');
    }

    /**
     * Get the organ service.
     *
     * @return Export\Service\Organ
     */
    public function getOrganService()
    {
        return $this->getServiceLocator()->get('export_service_organ');
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
