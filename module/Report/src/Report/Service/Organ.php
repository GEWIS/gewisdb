<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Report\Model\Organ as ReportOrgan;

class Organ extends AbstractService
{

    /**
     * Export organ info.
     */
    public function generate()
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $foundationRepo = $em->getRepository('Report\Model\SubDecision\Foundation');
        $repo = $em->getRepository('Report\Model\Organ');

        $organs = $foundationRepo->findAll();
        foreach ($organs as $organ) {
            // see if there already is an organ
            $repOrgan = $organ->getOrgan();
            if (null === $repOrgan) {
                $repOrgan = new ReportOrgan();
                $repOrgan->setFoundation($organ);
            }
            $repOrgan->setAbbr($organ->getAbbr());
            $repOrgan->setName($organ->getName());
            $repOrgan->setType($organ->getOrganType());
            $repOrgan->setFoundationDate($organ->getDecision()->getMeeting()->getDate());
            var_dump($repOrgan);
        }
        // find all organs
        // - assign ID
        // - Set basic info
        // - get foundation date
        // - if abrogated, set abrogation date

        // then, for every organ
        // - find members
        //   - assign installation
        //   - find installation date
        //   - find discharge date
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
