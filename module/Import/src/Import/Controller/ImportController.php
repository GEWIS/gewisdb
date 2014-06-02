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

        // TODO: save this somewhere
        $lastDate = '1900-01-01';

        $stmt = $db->prepare("SELECT v.*, t.* FROM vergadering AS v
            INNER JOIN vergadertype AS t ON (v.vergadertypeid = t.vergadertypeid)
            WHERE v.datum >= ?
            ORDER BY v.datum");
        $stmt->bindParam(1, $lastDate);
        $stmt->execute();

        // statement to get all decisions
        $dStmt = $db->prepare("SELECT b.*, s.*, bs.*, b.inhoud as b_inhoud FROM besluit AS b
            INNER JOIN subbesluit AS s ON (s.vergadertypeid = b.vergadertypeid AND s.vergadernr = b.vergadernr AND s.puntnr = b.puntnr AND s.besluitnr = b.besluitnr)
            INNER JOIN besluittype AS bs ON (s.besluittypeid = bs.besluittypeid)
            WHERE b.vergadertypeid = :type AND b.vergadernr = :nr
            ORDER BY b.puntnr ASC, b.besluitnr ASC");

        while (($vergadering = $stmt->fetch()) != null) {
            //echo $vergadering['vergaderafk'] . ' ' . $vergadering['vergadernr'] . " ( " . $vergadering['datum'] . "):\n";
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
                    $console->readChar();
                    echo "Besluit " . $vergadering['vergaderafk'] . ' ' . $vergadering['vergadernr'] . '.' . $row['puntnr'] . '.' . $row['besluitnr'] . "\n";
                    $punt = $row['puntnr'];
                    $besluit = $row['besluitnr'];
                    echo $row['b_inhoud'] . "\n";
                }
                echo $row['subbesluitnr'] . ': ' . $row['inhoud'] . "\n";
                echo "\tType:\t\t{$row['besluittype']}\n";
                echo "\tLid:\t\t{$row['lidnummer']}\n";
                echo "\tFunctie:\t<TODO>\n";
                echo "\tOrgaan:\t\t<TODO>\n";
            }
            $console->readChar();

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
