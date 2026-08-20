<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Report\BoardMember;
use App\Entity\Report\SubDecision\Board\Discharge as ReportBoardDischarge;
use App\Entity\Report\SubDecision\Board\Installation as ReportBoardInstallation;
use App\Entity\Report\SubDecision\Board\Release as ReportBoardRelease;
use Doctrine\ORM\EntityManagerInterface;
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
        $boardMember = $this->findBoardMember($installation);

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

    public function generateDischarge(ReportBoardDischarge $discharge): void
    {
        $boardMember = $this->findBoardMember($discharge->getInstallation());

        if (null === $boardMember) {
            // The installation this discharge undoes never took effect, so there is nobody on the board to discharge.
            // That is what the ledger says whenever the installation was annulled before this point, and it is also
            // what the older meetings say, from before board membership was recorded the way it is now.
            return;
        }

        $boardMember->setDischargeDate($discharge->getDecision()->getMeeting()->getDate());

        $this->emReport->persist($boardMember);
    }

    public function generateRelease(ReportBoardRelease $release): void
    {
        $boardMember = $this->findBoardMember($release->getInstallation());

        if (null === $boardMember) {
            // See generateDischarge(): there is nothing to release somebody from.
            return;
        }

        $boardMember->setReleaseDate($release->getDate());

        $this->emReport->persist($boardMember);
    }

    /**
     * Find the board member an installation brought into being, if it has one.
     *
     * The installation's board member is the inverse side of the relation; it is only hydrated when the installation
     * is (re)loaded in a fresh session. Within a single session it is kept in step by hand, but an installation that
     * was never installed here has neither, so fall back to looking the board member up by its installation.
     */
    private function findBoardMember(ReportBoardInstallation $installation): ?BoardMember
    {
        $rp = new ReflectionProperty(
            ReportBoardInstallation::class,
            'boardMember',
        );

        if ($rp->isInitialized($installation)) {
            return $installation->getBoardMember();
        }

        return $this->emReport->getRepository(BoardMember::class)
            ->findOneBy(['installationDec' => $installation]);
    }
}
