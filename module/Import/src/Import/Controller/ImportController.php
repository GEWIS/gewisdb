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
        $dStmt = $db->prepare("SELECT b.* FROM besluit AS b
            WHERE b.vergadertypeid = :type AND b.vergadernr = :nr
            ORDER BY puntnr ASC, besluitnr ASC");

        while (($vergadering = $stmt->fetch()) != null) {
            echo $vergadering['vergaderafk'] . ' ' . $vergadering['vergadernr'] . " ( " . $vergadering['datum'] . "):\n";
            echo "-----------------------------------------\n";

            $dStmt->execute(array(
                'type' => $vergadering['vergadertypeid'],
                'nr' => $vergadering['vergadernr']
            ));

            $rows = $dStmt->fetchAll();

            if (empty($rows)) {
                continue;
            }

            var_dump($rows);

            echo "-----------------------------------------\n";
            sleep(2);
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
