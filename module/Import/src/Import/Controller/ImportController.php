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

        $query = $this->getServiceLocator()->get('import_database_query');

        // statement to get all decisions
        $dStmt = $db->prepare("SELECT b.*, s.*, bs.*, f.*, o.*, b.inhoud as b_inhoud FROM besluit AS b
            INNER JOIN subbesluit AS s ON (s.vergadertypeid = b.vergadertypeid AND s.vergadernr = b.vergadernr AND s.puntnr = b.puntnr AND s.besluitnr = b.besluitnr)
            INNER JOIN besluittype AS bs ON (s.besluittypeid = bs.besluittypeid)
            LEFT JOIN functie AS f ON (f.functieid = s.functieid)
            LEFT JOIN orgaan AS o ON (o.orgaanid = s.orgaanid)
            WHERE b.vergadertypeid = :type AND b.vergadernr = :nr
            ORDER BY b.puntnr ASC, b.besluitnr ASC");

        while (($vergadering = $query->fetchMeeting()) != null) {
            $verg = $vergadering['vergaderafk'] . ' ' . $vergadering['vergadernr'] . " (" . $vergadering['datum'] . ")";
            echo "Voeg vergadering $verg toe? [Y/n] ";
            $char = $console->readChar();
            echo "$char\n";
            //echo "-----------------------------------------\n";

            $dStmt->execute(array(
                'type' => $vergadering['vergadertypeid'],
                'nr' => $vergadering['vergadernr']
            ));

            $rows = $dStmt->fetchAll();

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
