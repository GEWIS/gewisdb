<?php

declare(strict_types=1);

namespace Report\Service;

use Doctrine\ORM\EntityManager;
use ReflectionProperty;
use Report\Model\BoardMember as BoardMemberModel;
use Report\Model\Keyholder as KeyholderModel;
use Report\Model\Organ as OrganModel;
use Report\Model\OrganMember as OrganMemberModel;
use Report\Model\SubDecision as SubDecisionModel;
use Report\Model\SubDecision\Abrogation as AbrogationModel;
use Report\Model\SubDecision\Annulment as AnnulmentModel;
use Report\Model\SubDecision\Board\Discharge as BoardDischargeModel;
use Report\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Report\Model\SubDecision\Board\Release as BoardReleaseModel;
use Report\Model\SubDecision\Discharge as DischargeModel;
use Report\Model\SubDecision\Foundation as FoundationModel;
use Report\Model\SubDecision\FoundationReference as FoundationReferenceModel;
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
     * built, so there may be nothing to revert yet. It is equally tolerant of a subdecision that no longer has the
     * subdecision it points at, which is how a row that GEWISDB has since replaced by one of another kind arrives
     * here to be cleaned up.
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
                if ($this->stillReferences($subDecision)) {
                    $this->findBoardMember($subDecision->getInstallation())?->setReleaseDate(null);
                }

                break;

            case $subDecision instanceof BoardDischargeModel:
                if ($this->stillReferences($subDecision)) {
                    $this->findBoardMember($subDecision->getInstallation())?->setDischargeDate(null);
                }

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
                if ($this->stillReferences($subDecision)) {
                    $this->findKeyholder($subDecision->getGranting())?->setWithdrawnDate(null);
                }

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
                $organ = $this->findFoundedOrgan($subDecision);
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

            case $subDecision instanceof InstallationModel:
                $organMember = $this->findOrganMember($subDecision);
                $organ = $this->findFoundedOrgan($subDecision);
                $organ?->removeSubdecision($subDecision);
                $subDecision->clearOrganMember();

                if (null !== $organMember) {
                    $organ?->getMembers()->removeElement($organMember);
                    $this->emReport->remove($organMember);
                }

                break;

            case $subDecision instanceof DischargeModel:
                if (!$this->stillReferences($subDecision)) {
                    break;
                }

                $installation = $subDecision->getInstallation();
                $organ = $this->findFoundedOrgan($installation);
                $organ?->removeSubdecision($subDecision);

                // An abolished organ discharges whoever is left in it, so that date must survive the revert.
                $this->findOrganMember($installation)?->setDischargeDate($organ?->getAbrogationDate());
                break;

            case $subDecision instanceof ReappointmentModel:
                // Reappointments do not produce related entities, they only extend an installation.
                if ($this->stillReferences($subDecision)) {
                    $subDecision->getInstallation()->removeReappointment($subDecision);
                }

                break;
        }
    }

    /**
     * Whether the subdecision still has the subdecision, or decision, that it points at.
     *
     * A subdecision that turned out to be wrong is put right by replacing it with one of another kind, and ReportDB
     * can be left holding the old one without what it pointed at. Doctrine leaves a typed property without a default
     * uninitialised when the columns behind it are empty, so such a subdecision cannot simply be asked for it. A
     * subdecision that points at nothing to begin with has nothing to be missing, and so always says yes.
     */
    public function stillReferences(SubDecisionModel $subDecision): bool
    {
        [$declaringClass, $property] = match (true) {
            $subDecision instanceof BoardReleaseModel => [BoardReleaseModel::class, 'installation'],
            $subDecision instanceof BoardDischargeModel => [BoardDischargeModel::class, 'installation'],
            $subDecision instanceof KeyWithdrawalModel => [KeyWithdrawalModel::class, 'granting'],
            $subDecision instanceof DischargeModel => [DischargeModel::class, 'installation'],
            $subDecision instanceof ReappointmentModel => [ReappointmentModel::class, 'installation'],
            $subDecision instanceof AnnulmentModel => [AnnulmentModel::class, 'target'],
            // Both installations and abrogations point back at the foundation of the organ they are about.
            $subDecision instanceof FoundationReferenceModel => [FoundationReferenceModel::class, 'foundation'],
            default => [null, null],
        };

        if (null === $declaringClass) {
            return true;
        }

        return (new ReflectionProperty($declaringClass, $property))->isInitialized($subDecision);
    }

    /**
     * Find the organ that the subdecision's foundation brought into being, if it still has one.
     */
    private function findFoundedOrgan(FoundationReferenceModel $subDecision): ?OrganModel
    {
        if (!$this->stillReferences($subDecision)) {
            return null;
        }

        return $this->findOrgan($subDecision->getFoundation());
    }

    /**
     * Take the subdecision out of every organ that lists it among the decisions it was shaped by.
     *
     * Reverting already does that for the organ a subdecision was about, but a subdecision that is about to be
     * deleted has to be let go of by any organ at all, including one it can no longer point back to.
     */
    public function detachFromOrgans(SubDecisionModel $subDecision): void
    {
        // A subdecision is identified by the decision it belongs to and its place in it, and a composite identity
        // like that cannot be handed to a query as one value, so it goes in field by field.
        $organs = $this->emReport->getRepository(OrganModel::class)
            ->createQueryBuilder('o')
            ->innerJoin('o.subdecisions', 's')
            ->where('s.meeting_type = :meetingType')
            ->andWhere('s.meeting_number = :meetingNumber')
            ->andWhere('s.decision_point = :decisionPoint')
            ->andWhere('s.decision_number = :decisionNumber')
            ->andWhere('s.sequence = :sequence')
            ->setParameter('meetingType', $subDecision->getMeetingType())
            ->setParameter('meetingNumber', $subDecision->getMeetingNumber())
            ->setParameter('decisionPoint', $subDecision->getDecisionPoint())
            ->setParameter('decisionNumber', $subDecision->getDecisionNumber())
            ->setParameter('sequence', $subDecision->getSequence())
            ->getQuery()
            ->getResult();

        /** @var OrganModel $organ */
        foreach ($organs as $organ) {
            $organ->removeSubdecision($subDecision);
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
