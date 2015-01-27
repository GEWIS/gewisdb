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
        // TODO: export meetings and decisions
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
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
