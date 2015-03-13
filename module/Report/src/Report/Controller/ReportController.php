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

        echo "generating report database\n";
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
