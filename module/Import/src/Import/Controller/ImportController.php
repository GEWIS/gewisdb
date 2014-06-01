<?php

namespace Import\Controller;

use Zend\Mvc\Controller\AbstractActionController;

use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\DriverManager;

class ImportController extends AbstractActionController
{

    /**
     * Import action.
     */
    public function importAction()
    {
        $console = $this->getConsole();

        $conn = $this->getServiceLocator()->get('doctrine.connection.orm_import');
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
