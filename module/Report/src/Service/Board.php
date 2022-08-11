<?php

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use ReflectionProperty;
use Report\Model\BoardMember as BoardMemberModel;
use Report\Model\SubDecision\Board\Installation as InstallationModel;

class Board
{
    public function __construct(private readonly EntityManager $emReport)
    {
    }

    /**
     * Export board info.
     */
    public function generate()
    {
        $repo = $this->emReport->getRepository(InstallationModel::class);

        $installs = $repo->findAll();
        /** @var InstallationModel $install */
        foreach ($installs as $install) {
            $rp = new ReflectionProperty(InstallationModel::class, 'boardMember');
            if ($rp->isInitialized($install)) {
                $boardMember = $install->getBoardMember();
            } else {
                $boardMember = null;
            }

            if (null === $boardMember) {
                $boardMember = new BoardMemberModel();
                $boardMember->setInstallationDec($install);
            }

            $boardMember->setMember($install->getMember());
            $boardMember->setFunction($install->getFunction());
            $boardMember->setInstallDate($install->getDate());

            $rp = new ReflectionProperty(InstallationModel::class, 'release');
            if ($rp->isInitialized($install)) {
                $boardMember->setReleaseDate($install->getRelease()->getDate());
            } else {
                $boardMember->setReleaseDate(null);
            }

            $rp = new ReflectionProperty(InstallationModel::class, 'discharge');
            if ($rp->isInitialized($install)) {
                $boardMember->setDischargeDate($install->getDischarge()->getDecision()->getMeeting()->getDate());
            } else {
                $boardMember->setDischargeDate(null);
            }

            $this->emReport->persist($boardMember);
        }

        $this->emReport->flush();
    }
}
