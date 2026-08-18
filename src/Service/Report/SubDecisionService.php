<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Report\BoardMember;
use App\Entity\Report\Keyholder;
use App\Entity\Report\Organ;
use App\Entity\Report\OrganMember;
use App\Entity\Report\SubDecision;
use App\Entity\Report\SubDecision\Abrogation;
use App\Entity\Report\SubDecision\Board\Discharge as BoardDischarge;
use App\Entity\Report\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Report\SubDecision\Board\Release as BoardRelease;
use App\Entity\Report\SubDecision\Discharge;
use App\Entity\Report\SubDecision\Foundation;
use App\Entity\Report\SubDecision\Installation;
use App\Entity\Report\SubDecision\Key\Granting as KeyGranting;
use App\Entity\Report\SubDecision\Key\Withdrawal as KeyWithdrawal;
use App\Entity\Report\SubDecision\Reappointment;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class SubDecisionService
{
    public function __construct(
        #[Autowire(service: 'doctrine.orm.report_entity_manager')]
        private readonly EntityManagerInterface $emReport,
        private readonly BoardService $boardService,
        private readonly KeyholderService $keyholderService,
        private readonly OrganService $organService,
    ) {
    }

    /**
     * Generates related entities of a subdecision into ReportDB.
     */
    public function generateRelated(SubDecision $subDecision): void
    {
        switch (true) {
            // Board-related
            case $subDecision instanceof BoardInstallation:
                $this->boardService->generateInstallation($subDecision);
                break;

            case $subDecision instanceof BoardRelease:
                $this->boardService->generateRelease($subDecision);
                break;

            case $subDecision instanceof BoardDischarge:
                $this->boardService->generateDischarge($subDecision);
                break;

            // Keyholder-related
            case $subDecision instanceof KeyGranting:
                $this->keyholderService->generateGranting($subDecision);
                break;

            case $subDecision instanceof KeyWithdrawal:
                $this->keyholderService->generateWithdrawal($subDecision);
                break;

            // Organ-related
            case $subDecision instanceof Foundation:
                $this->organService->generateFoundation($subDecision);
                break;

            case $subDecision instanceof Abrogation:
                $this->organService->generateAbrogation($subDecision);
                break;

            case $subDecision instanceof Installation:
                $this->organService->generateInstallation($subDecision);
                break;

            case $subDecision instanceof Discharge:
                $this->organService->generateDischarge($subDecision);
                break;
        }

        $this->emReport->persist($subDecision);
    }

    /**
     * Reverts the related entities of a subdecision in ReportDB, i.e. undoes {@see self::generateRelated()}.
     *
     * The subdecision itself is left alone: it remains part of the historical record, it is its decision that is
     * marked as annulled. Only the entities that were derived from it are removed or reset.
     *
     * Whether reverting is allowed at all is decided in the `Decision` domain, which owns the ledger; by the time a
     * subdecision gets here it may simply be reverted. Reverting is however tolerant of missing related entities:
     * during a full (re)generation the decisions are processed before the organ, board, and keyholder tables are
     * built, so there may be nothing to revert yet.
     */
    public function revertRelated(SubDecision $subDecision): void
    {
        switch (true) {
            // Board-related
            case $subDecision instanceof BoardInstallation:
                $boardMember = $this->findBoardMember($subDecision);
                $subDecision->clearBoardMember();

                if (null !== $boardMember) {
                    $this->emReport->remove($boardMember);
                }

                break;

            case $subDecision instanceof BoardRelease:
                $this->findBoardMember($subDecision->getInstallation())?->setReleaseDate(null);
                break;

            case $subDecision instanceof BoardDischarge:
                $this->findBoardMember($subDecision->getInstallation())?->setDischargeDate(null);
                break;

            // Keyholder-related
            case $subDecision instanceof KeyGranting:
                $keyholder = $this->findKeyholder($subDecision);
                $subDecision->clearKeyholder();

                if (null !== $keyholder) {
                    $this->emReport->remove($keyholder);
                }

                break;

            case $subDecision instanceof KeyWithdrawal:
                $this->findKeyholder($subDecision->getGranting())?->setWithdrawnDate(null);
                break;

            // Organ-related
            case $subDecision instanceof Foundation:
                $organ = $this->findOrgan($subDecision);
                $subDecision->clearOrgan();

                if (null !== $organ) {
                    $this->emReport->remove($organ);
                }

                break;

            case $subDecision instanceof Abrogation:
                $organ = $this->findOrgan($subDecision->getFoundation());
                $organ?->removeSubdecision($subDecision);

                if (null !== $organ) {
                    // Abolishing the organ discharged whoever was still in it, so those discharges go as well.
                    foreach ($organ->getMembers() as $organMember) {
                        $discharge = $organMember->getInstallation()->getDischarge();
                        $organMember->setDischargeDate($discharge?->getDecision()->getMeeting()->getDate());
                    }

                    $organ->setAbrogationDate(null);
                }

                break;

            case $subDecision instanceof Installation:
                $organMember = $this->findOrganMember($subDecision);
                $organ = $this->findOrgan($subDecision->getFoundation());
                $organ?->removeSubdecision($subDecision);
                $subDecision->clearOrganMember();

                if (null !== $organMember) {
                    $organ?->getMembers()->removeElement($organMember);
                    $this->emReport->remove($organMember);
                }

                break;

            case $subDecision instanceof Discharge:
                $installation = $subDecision->getInstallation();
                $organ = $this->findOrgan($installation->getFoundation());
                $organ?->removeSubdecision($subDecision);

                // An abolished organ discharges whoever is left in it, so that date must survive the revert.
                $this->findOrganMember($installation)?->setDischargeDate($organ?->getAbrogationDate());
                break;

            case $subDecision instanceof Reappointment:
                // Reappointments do not produce related entities, they only extend an installation.
                $subDecision->getInstallation()->removeReappointment($subDecision);
                break;
        }
    }

    private function findOrgan(Foundation $foundation): ?Organ
    {
        return $this->emReport->getRepository(Organ::class)
            ->findOneBy(['foundation' => $foundation]);
    }

    private function findOrganMember(Installation $installation): ?OrganMember
    {
        return $this->emReport->getRepository(OrganMember::class)
            ->findOneBy(['installation' => $installation]);
    }

    private function findBoardMember(BoardInstallation $installation): ?BoardMember
    {
        return $this->emReport->getRepository(BoardMember::class)
            ->findOneBy(['installationDec' => $installation]);
    }

    private function findKeyholder(KeyGranting $granting): ?Keyholder
    {
        return $this->emReport->getRepository(Keyholder::class)
            ->findOneBy(['grantingDec' => $granting]);
    }
}
