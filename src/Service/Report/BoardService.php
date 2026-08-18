<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Report\BoardMember;
use App\Entity\Report\SubDecision\Board\Discharge as ReportBoardDischarge;
use App\Entity\Report\SubDecision\Board\Installation as ReportBoardInstallation;
use App\Entity\Report\SubDecision\Board\Release as ReportBoardRelease;
use Doctrine\ORM\EntityManagerInterface;
use LogicException;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class BoardService
{
    public function __construct(
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
    ) {
    }

    public function generateInstallation(ReportBoardInstallation $installation): BoardMember
    {
        $rp = new ReflectionProperty(ReportBoardInstallation::class, 'boardMember');
        if ($rp->isInitialized($installation)) {
            $boardMember = $installation->getBoardMember();
        } else {
            $boardMember = null;
        }

        if (null === $boardMember) {
            $boardMember = new BoardMember();
            $boardMember->setInstallationDec($installation);
            $installation->setBoardMember($boardMember);
        }

        $boardMember->setMember($installation->getMember());
        $boardMember->setFunction($installation->getFunction());
        $boardMember->setInstallDate($installation->getDate());

        $this->emReport->persist($boardMember);

        return $boardMember;
    }

    public function generateDischarge(ReportBoardDischarge $discharge): BoardMember
    {
        $rp = new ReflectionProperty(ReportBoardInstallation::class, 'boardMember');
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

    public function generateRelease(ReportBoardRelease $release): BoardMember
    {
        $rp = new ReflectionProperty(ReportBoardInstallation::class, 'boardMember');
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
