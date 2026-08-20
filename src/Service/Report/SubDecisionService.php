<?php

declare(strict_types=1);

namespace App\Service\Report;

use App\Entity\Report\BoardMember;
use App\Entity\Report\Keyholder;
use App\Entity\Report\Organ;
use App\Entity\Report\OrganMember;
use App\Entity\Report\SubDecision;
use App\Entity\Report\SubDecision\Abrogation;
use App\Entity\Report\SubDecision\Annulment;
use App\Entity\Report\SubDecision\Board\Discharge as BoardDischarge;
use App\Entity\Report\SubDecision\Board\Installation as BoardInstallation;
use App\Entity\Report\SubDecision\Board\Release as BoardRelease;
use App\Entity\Report\SubDecision\Discharge;
use App\Entity\Report\SubDecision\Foundation;
use App\Entity\Report\SubDecision\FoundationReference;
use App\Entity\Report\SubDecision\Installation;
use App\Entity\Report\SubDecision\Key\Granting as KeyGranting;
use App\Entity\Report\SubDecision\Key\Withdrawal as KeyWithdrawal;
use App\Entity\Report\SubDecision\Reappointment;
use Doctrine\ORM\EntityManagerInterface;
use ReflectionProperty;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

use function assert;

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
                if ($this->stillReferences($subDecision)) {
                    $this->findBoardMember($subDecision->getInstallation())?->setReleaseDate(null);
                }

                break;

            case $subDecision instanceof BoardDischarge:
                if ($this->stillReferences($subDecision)) {
                    $this->findBoardMember($subDecision->getInstallation())?->setDischargeDate(null);
                }

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
                if ($this->stillReferences($subDecision)) {
                    $this->findKeyholder($subDecision->getGranting())?->setWithdrawnDate(null);
                }

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

            case $subDecision instanceof Installation:
                $organMember = $this->findOrganMember($subDecision);
                $organ = $this->findFoundedOrgan($subDecision);
                $organ?->removeSubdecision($subDecision);
                $subDecision->clearOrganMember();

                if (null !== $organMember) {
                    $organ?->getMembers()->removeElement($organMember);
                    $this->emReport->remove($organMember);
                }

                break;

            case $subDecision instanceof Discharge:
                if (!$this->stillReferences($subDecision)) {
                    break;
                }

                $installation = $subDecision->getInstallation();
                $organ = $this->findFoundedOrgan($installation);
                $organ?->removeSubdecision($subDecision);

                // An abolished organ discharges whoever is left in it, so that date must survive the revert.
                $this->findOrganMember($installation)?->setDischargeDate($organ?->getAbrogationDate());
                break;

            case $subDecision instanceof Reappointment:
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
    public function stillReferences(SubDecision $subDecision): bool
    {
        [
            $declaringClass, $property
        ] = match (true) {
            $subDecision instanceof BoardRelease => [
                BoardRelease::class,
                'installation',
            ],
            $subDecision instanceof BoardDischarge => [
                BoardDischarge::class,
                'installation',
            ],
            $subDecision instanceof KeyWithdrawal => [
                KeyWithdrawal::class,
                'granting',
            ],
            $subDecision instanceof Discharge => [
                Discharge::class,
                'installation',
            ],
            $subDecision instanceof Reappointment => [
                Reappointment::class,
                'installation',
            ],
            $subDecision instanceof Annulment => [
                Annulment::class,
                'target',
            ],
            // Both installations and abrogations point back at the foundation of the body they are about.
            $subDecision instanceof FoundationReference => [
                FoundationReference::class,
                'foundation',
            ],
            default => [
                null,
                null,
            ],
        };

        if (null === $declaringClass) {
            return true;
        }

        return new ReflectionProperty($declaringClass, $property)->isInitialized($subDecision);
    }

    /**
     * Take the subdecision out of every body that lists it among the decisions it was shaped by.
     *
     * Reverting already does that for the body a subdecision was about, but a subdecision that is about to be deleted
     * has to be let go of by any body at all, including one it can no longer point back to.
     */
    public function detachFromOrgans(SubDecision $subDecision): void
    {
        // A subdecision is identified by the decision it belongs to and its place in it, and a composite identity
        // like that cannot be handed to a query as one value, so it goes in field by field.
        $organs = $this->emReport->getRepository(Organ::class)
            ->createQueryBuilder('o')
            ->innerJoin(
                'o.subdecisions',
                's',
            )
            ->where('s.meeting_type = :meetingType')
            ->andWhere('s.meeting_number = :meetingNumber')
            ->andWhere('s.decision_point = :decisionPoint')
            ->andWhere('s.decision_number = :decisionNumber')
            ->andWhere('s.sequence = :sequence')
            ->setParameter(
                'meetingType',
                $subDecision->getMeetingType(),
            )
            ->setParameter(
                'meetingNumber',
                $subDecision->getMeetingNumber(),
            )
            ->setParameter(
                'decisionPoint',
                $subDecision->getDecisionPoint(),
            )
            ->setParameter(
                'decisionNumber',
                $subDecision->getDecisionNumber(),
            )
            ->setParameter(
                'sequence',
                $subDecision->getSequence(),
            )
            ->getQuery()
            ->getResult();

        foreach ($organs as $organ) {
            assert($organ instanceof Organ);
            $organ->removeSubdecision($subDecision);
        }
    }

    /**
     * Find the body that the subdecision's foundation brought into being, if it still has one.
     */
    private function findFoundedOrgan(FoundationReference $subDecision): ?Organ
    {
        if (!$this->stillReferences($subDecision)) {
            return null;
        }

        return $this->findOrgan($subDecision->getFoundation());
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
