<?php

namespace Report\Service;

use Application\Service\AbstractService;

use Report\Model\Organ as ReportOrgan;
use Report\Model\OrganMember;
use Report\Model\SubDecision\Abrogation;
use Report\Model\SubDecision\Installation;

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

        $foundations = $foundationRepo->findAll();
        foreach ($foundations as $foundation) {
            // see if there already is an organ
            $repOrgan = $foundation->getOrgan();
            if (null === $repOrgan) {
                $repOrgan = new ReportOrgan();
                $repOrgan->setFoundation($foundation);
            }
            $repOrgan->setAbbr($foundation->getAbbr());
            $repOrgan->setName($foundation->getName());
            $repOrgan->setType($foundation->getOrganType());
            $repOrgan->setFoundationDate($foundation->getDecision()->getMeeting()->getDate());

            // get the abrogation date and find organ members
            foreach ($foundation->getReferences() as $ref) {
                if ($ref instanceof Abrogation) {
                    $repOrgan->setAbrogationDate($ref->getDecision()->getMeeting()->getDate());
                }
                if ($ref instanceof Installation) {
                    // get full reference
                    $organMember = $ref->getOrganMember();
                    if (null === $organMember) {
                        $organMember = new OrganMember();
                        // set the ID stuff
                        $organMember->setOrgan($repOrgan);
                        $organMember->setMember($ref->getMember());
                        $function = $ref->getFunction();
                        if (null === $function)
                            $function = 'Lid';
                        $organMember->setFunction($function);
                        $organMember->setInstallDate($ref->getDecision()->getMeeting()->getDate());
                    }
                    $organMember->setInstallation($ref);
                    $discharge = $ref->getDischarge();
                    if (null !== $discharge) {
                        $organMember->setDischargeDate($discharge->getDecision()->getMeeting()->getDate());
                    }

                    $em->persist($organMember);
                }
            }
            $em->persist($repOrgan);
        }
        $em->flush();
    }

    /**
     * Get the console object.
     */
    public function getConsole()
    {
        return $this->getServiceManager()->get('console');
    }
}
