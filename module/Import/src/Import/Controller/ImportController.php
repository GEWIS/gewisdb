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

        $db = $this->getServiceLocator()->get('doctrine.connection.orm_import');
        $meetingService = $this->getServiceLocator()->get('import_service_meeting');
        $query = $this->getServiceLocator()->get('import_database_query');

        $meetings = $meetingService->getMeetings();

        foreach ($meetings as $vergadering)  {
            $verg = $vergadering['vergaderafk'] . ' ' . $vergadering['vergadernr'] . " (" . $vergadering['datum'] . ")";
            echo "Voeg vergadering $verg toe? [Y/n] ";
            $char = $console->readChar();
            echo "$char\n";
            //echo "-----------------------------------------\n";

            $rows = $query->fetchDecisions($vergadering['vergadertypeid'], $vergadering['vergadernr']);

            if (empty($rows)) {
                continue;
            }

            $punt = -1;
            $besluit = -1;

            foreach ($rows as $row) {
                if ($row['puntnr'] != $punt || $row['besluitnr'] != $besluit) {
                    echo "Besluit " . $vergadering['vergaderafk'] . ' ' . $vergadering['vergadernr'] . '.' . $row['puntnr'] . '.' . $row['besluitnr'] . "\n";
                    $punt = $row['puntnr'];
                    $besluit = $row['besluitnr'];
                    echo $row['b_inhoud'] . "\n";
                }
                echo $row['subbesluitnr'] . ': ' . $row['inhoud'] . "\n";
                echo "\tType:\t\t{$row['besluittype']}\n";
                echo "\tLid:\t\t{$row['lidnummer']}\n";
                echo "\tFunctie:\t{$row['functie']}\n";
                echo "\tOrgaan:\t\t{$row['orgaanafk']}\n";
                echo "\n";
                $console->readChar();

            }

            // TODO: interface to build new decision from this

            //echo "-----------------------------------------\n";
        }
    }

    /**
     * Get the console object.
     */
    protected function getConsole()
    {
        return $this->getServiceLocator()->get('console');
    }
}
