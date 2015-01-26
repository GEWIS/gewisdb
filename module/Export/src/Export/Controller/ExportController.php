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

        // TODO: export members
        // TODO: export meetings and decisions
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
