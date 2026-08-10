<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use Report\Model\BoardMember as BoardMemberModel;
use Report\Model\Keyholder as KeyholderModel;
use Report\Model\Organ as OrganModel;
use Report\Model\OrganMember as OrganMemberModel;
use Report\Model\SubDecision as SubDecisionModel;
use Report\Model\SubDecision\Abrogation as AbrogationModel;
use Report\Model\SubDecision\Board\Discharge as BoardDischargeModel;
use Report\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Report\Model\SubDecision\Board\Release as BoardReleaseModel;
use Report\Model\SubDecision\Discharge as DischargeModel;
use Report\Model\SubDecision\Foundation as FoundationModel;
use Report\Model\SubDecision\Installation as InstallationModel;
use Report\Model\SubDecision\Key\Granting as KeyGrantingModel;
use Report\Model\SubDecision\Key\Withdrawal as KeyWithdrawalModel;
use Report\Model\SubDecision\Reappointment as ReappointmentModel;
use Report\Service\Board as BoardService;
use Report\Service\Keyholder as KeyholderService;
use Report\Service\Organ as OrganService;

class SubDecision
{
    public function __construct(
        private readonly EntityManager $emReport,
        private readonly BoardService $boardService,
        private readonly KeyholderService $keyholderService,
        private readonly OrganService $organService,
    ) {
    }

    /**
     * Generates related entities of a subdecision into ReportDB.
     */
    public function generateRelated(SubDecisionModel $subDecision): void
    {
        switch (true) {
            // Board-related
            case $subDecision instanceof BoardInstallationModel:
                $this->boardService->generateInstallation($subDecision);
                break;

            case $subDecision instanceof BoardReleaseModel:
                $this->boardService->generateRelease($subDecision);
                break;

            case $subDecision instanceof BoardDischargeModel:
                $this->boardService->generateDischarge($subDecision);
                break;

            // Keyholder-related
            case $subDecision instanceof KeyGrantingModel:
                $this->keyholderService->generateGranting($subDecision);
                break;

            case $subDecision instanceof KeyWithdrawalModel:
                $this->keyholderService->generateWithdrawal($subDecision);
                break;

            // Organ-related
            case $subDecision instanceof FoundationModel:
                $this->organService->generateFoundation($subDecision);
                break;

            case $subDecision instanceof AbrogationModel:
                $this->organService->generateAbrogation($subDecision);
                break;

            case $subDecision instanceof InstallationModel:
                $this->organService->generateInstallation($subDecision);
                break;

            case $subDecision instanceof DischargeModel:
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
     * Whether reverting is allowed at all is decided in the `Database` module, which owns the ledger; by the time a
     * subdecision gets here it may simply be reverted. Reverting is however tolerant of missing related entities:
     * during a full (re)generation the decisions are processed before the organ, board, and keyholder tables are
     * built, so there may be nothing to revert yet.
     */
    public function revertRelated(SubDecisionModel $subDecision): void
    {
        switch (true) {
            // Board-related
            case $subDecision instanceof BoardInstallationModel:
                $boardMember = $this->findBoardMember($subDecision);
                $subDecision->clearBoardMember();

                if (null !== $boardMember) {
                    $this->emReport->remove($boardMember);
                }

                break;

            case $subDecision instanceof BoardReleaseModel:
                $this->findBoardMember($subDecision->getInstallation())?->setReleaseDate(null);
                break;

            case $subDecision instanceof BoardDischargeModel:
                $this->findBoardMember($subDecision->getInstallation())?->setDischargeDate(null);
                break;

            // Keyholder-related
            case $subDecision instanceof KeyGrantingModel:
                $keyholder = $this->findKeyholder($subDecision);
                $subDecision->clearKeyholder();

                if (null !== $keyholder) {
                    $this->emReport->remove($keyholder);
                }

                break;

            case $subDecision instanceof KeyWithdrawalModel:
                $this->findKeyholder($subDecision->getGranting())?->setWithdrawnDate(null);
                break;

            // Organ-related
            case $subDecision instanceof FoundationModel:
                $organ = $this->findOrgan($subDecision);
                $subDecision->clearOrgan();

                if (null !== $organ) {
                    $this->emReport->remove($organ);
                }

                break;

            case $subDecision instanceof AbrogationModel:
                $organ = $this->findOrgan($subDecision->getFoundation());
                $organ?->setAbrogationDate(null);
                $organ?->removeSubdecision($subDecision);
                break;

            case $subDecision instanceof InstallationModel:
                $organMember = $this->findOrganMember($subDecision);
                $organ = $this->findOrgan($subDecision->getFoundation());
                $organ?->removeSubdecision($subDecision);
                $subDecision->clearOrganMember();

                if (null !== $organMember) {
                    $organ?->getMembers()->removeElement($organMember);
                    $this->emReport->remove($organMember);
                }

                break;

            case $subDecision instanceof DischargeModel:
                $installation = $subDecision->getInstallation();
                $organ = $this->findOrgan($installation->getFoundation());
                $organ?->removeSubdecision($subDecision);

                // An abolished organ discharges whoever is left in it, so that date must survive the revert.
                $this->findOrganMember($installation)?->setDischargeDate($organ?->getAbrogationDate());
                break;

            case $subDecision instanceof ReappointmentModel:
                // Reappointments do not produce related entities, they only extend an installation.
                $subDecision->getInstallation()->removeReappointment($subDecision);
                break;
        }
    }

    private function findOrgan(FoundationModel $foundation): ?OrganModel
    {
        return $this->emReport->getRepository(OrganModel::class)
            ->findOneBy(['foundation' => $foundation]);
    }

    private function findOrganMember(InstallationModel $installation): ?OrganMemberModel
    {
        return $this->emReport->getRepository(OrganMemberModel::class)
            ->findOneBy(['installation' => $installation]);
    }

    private function findBoardMember(BoardInstallationModel $installation): ?BoardMemberModel
    {
        return $this->emReport->getRepository(BoardMemberModel::class)
            ->findOneBy(['installationDec' => $installation]);
    }

    private function findKeyholder(KeyGrantingModel $granting): ?KeyholderModel
    {
        return $this->emReport->getRepository(KeyholderModel::class)
            ->findOneBy(['grantingDec' => $granting]);
    }
}
