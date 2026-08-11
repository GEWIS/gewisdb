<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use LogicException;
use ReflectionProperty;
use Report\Model\BoardMember as BoardMemberModel;
use Report\Model\SubDecision\Board\Discharge as ReportBoardDischargeModel;
use Report\Model\SubDecision\Board\Installation as ReportBoardInstallationModel;
use Report\Model\SubDecision\Board\Release as ReportBoardReleaseModel;

class Board
{
    public function __construct(private readonly EntityManager $emReport)
    {
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
            $installation->setBoardMember($boardMember);
        }

        $boardMember->setMember($installation->getMember());
        $boardMember->setFunction($installation->getFunction());
        $boardMember->setInstallDate($installation->getDate());

        $this->emReport->persist($boardMember);

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

        return $boardMember;
    }
}
