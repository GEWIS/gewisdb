<?php

namespace Report\Service;

use Application\Service\AbstractService;
use Report\Model\SubDecision\Board\Installation;
use Report\Model\BoardMember;

class Board extends AbstractService
{
    /**
     * Export board info.
     */
    public function generate()
    {
        $em = $this->getServiceManager()->get('doctrine.entitymanager.orm_report');
        $repo = $em->getRepository('Report\Model\SubDecision\Board\Installation');

        $installs = $repo->findAll();
        foreach ($installs as $install) {
            $boardMember = $install->getBoardMember();

            if (null === $boardMember) {
                $boardMember = new BoardMember();
                $boardMember->setInstallationDec($install);
            }

            $boardMember->setMember($install->getMember());
            $boardMember->setFunction($install->getFunction());
            $boardMember->setInstallDate($install->getDate());

            $release = $install->getRelease();
            if (null !== $release) {
                $boardMember->setReleaseDate($release->getDate());
            }

            $discharge = $install->getDischarge();

            if (null !== $discharge) {
                $boardMember->setDischargeDate($discharge->getDecision()->getMeeting()->getDate());
            }

            $em->persist($boardMember);
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
