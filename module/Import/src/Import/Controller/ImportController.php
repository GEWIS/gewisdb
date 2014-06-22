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

        $meetingService = $this->getServiceLocator()->get('import_service_meeting');

        $meetings = $meetingService->getMeetings();

        foreach ($meetings as $vergadering)  {
            $verg = $vergadering['vergaderafk'] . ' ' . $vergadering['vergadernr'] . " (" . $vergadering['datum'] . ")";
            echo "Voeg vergadering $verg toe? [Y/n] ";
            $char = $console->readChar();
            echo "\n\n";

            if (strtolower($char) == 'n') {
                continue;
            }

            $meetingService->importMeeting($vergadering);
        }
    }

    /**
     * Member import action.
     */
    public function membersAction()
    {
        $query = $this->getServiceLocator()->get('import_database_query');
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
