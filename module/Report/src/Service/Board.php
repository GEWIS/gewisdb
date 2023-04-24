<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use LogicException;
use ReflectionProperty;
use Report\Model\BoardMember as BoardMemberModel;
use Report\Model\SubDecision\Board\{
    Discharge as ReportBoardDischargeModel,
    Installation as ReportBoardInstallationModel,
    Release as ReportBoardReleaseModel,
};

class Board
{
    public function __construct(private readonly EntityManager $emReport)
    {
    }

    /**
     * Export board info.
     */
    public function generate(): void
    {
        $repo = $this->emReport->getRepository(ReportBoardInstallationModel::class);

        $installs = $repo->findAll();
        /** @var ReportBoardInstallationModel $install */
        foreach ($installs as $install) {
            $boardMember = $this->generateInstallation($install);

            if (null !== $install->getRelease()) {
                $boardMember = $this->generateRelease($install->getRelease());
            }

            if (null !== $install->getDischarge()) {
                $boardMember = $this->generateDischarge($install->getDischarge());
            }

            $this->emReport->persist($boardMember);
        }

        $this->emReport->flush();
    }

    public function generateInstallation(ReportBoardInstallationModel $installation): BoardMemberModel
    {
        $rp = new ReflectionProperty(ReportBoardInstallationModel::class, 'boardMember');
        if ($rp->isInitialized($installation)) {
            $boardMember = $installation->getBoardMember();
        } else {
            $boardMember = null;
        }

        if (null === $boardMember) {
            $boardMember = new BoardMemberModel();
            $boardMember->setInstallationDec($installation);
        }

        $boardMember->setMember($installation->getMember());
        $boardMember->setFunction($installation->getFunction());
        $boardMember->setInstallDate($installation->getDate());

        $this->emReport->persist($boardMember);
        $this->emReport->flush();

        return $boardMember;
    }

    public function generateDischarge(ReportBoardDischargeModel $discharge): BoardMemberModel
    {
        $rp = new ReflectionProperty(ReportBoardInstallationModel::class, 'boardMember');
        if ($rp->isInitialized($discharge->getInstallation())) {
            $boardMember = $discharge->getInstallation()->getBoardMember();
        } else {
            $boardMember = null;
        }

        if (null === $boardMember) {
            throw new LogicException('Board discharge without a BoardMember');
        }

        $boardMember->setDischargeDate($discharge->getDecision()->getMeeting()->getDate());

        $this->emReport->persist($boardMember);
        $this->emReport->flush();

        return $boardMember;
    }

    public function generateRelease(ReportBoardReleaseModel $release): BoardMemberModel
    {
        $rp = new ReflectionProperty(ReportBoardInstallationModel::class, 'boardMember');
        if ($rp->isInitialized($release->getInstallation())) {
            $boardMember = $release->getInstallation()->getBoardMember();
        } else {
            $boardMember = null;
        }

        if (null === $boardMember) {
            throw new LogicException('Board release without a BoardMember');
        }

        $boardMember->setReleaseDate($release->getDate());

        $this->emReport->persist($boardMember);
        $this->emReport->flush();

        return $boardMember;
    }
}
