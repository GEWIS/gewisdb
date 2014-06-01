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

        /*
         * Read database connection info.
         */

        echo "Enter database information:\n";

        echo "Database name: ";
        $db = $console->readLine();

        echo "Database host [localhost]: ";
        $host = $console->readLine();
        if (empty($host)) {
            $host = 'localhost';
        }

        echo "Database username: ";
        $username = $console->readLine();

        echo "Database password: ";
        // BEWARE: ugly hack to hide password entry
        system('stty -echo');
        $password = $console->readLine();
        system('stty echo');
        echo "\n";

        /*
         * Open database connection.
         */
        $info = array(
            'dbname' => $db,
            'user' => $username,
            'password' => $password,
            'host' => $host,
            'driver' => 'pdo_pgsql'
        );

        $conn = DriverManager::getConnection($info, new Configuration());
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
