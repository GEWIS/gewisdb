<?php

declare(strict_types=1);

namespace App\Service\Database;

use App\Entity\Application\Enums\AppLanguages;
use App\Entity\Database\Decision as DecisionModel;
use App\Entity\Database\Enums\MeetingTypes;
use App\Entity\Database\Meeting as MeetingModel;
use App\Entity\Database\SubDecision;
use App\Entity\Database\SubDecision\Abrogation;
use App\Entity\Database\SubDecision\Annulment as AnnulmentModel;
use App\Entity\Database\SubDecision\Board\Discharge as BoardDischargeModel;
use App\Entity\Database\SubDecision\Board\Installation as BoardInstallationModel;
use App\Entity\Database\SubDecision\Discharge as DischargeModel;
use App\Entity\Database\SubDecision\Financial\Budget;
use App\Entity\Database\SubDecision\Foundation as FoundationModel;
use App\Entity\Database\SubDecision\Foundation;
use App\Entity\Database\SubDecision\Installation as InstallationModel;
use App\Exception\Database\AnnulmentNotPossible;
use App\Exception\Database\DecisionStillReferenced;
use App\Repository\Database\MeetingRepository;
use App\Repository\Database\SubDecision\FoundationRepository;
use App\Service\Report\ApiService;
use App\ViewModel\Database\DecisionOptions;
use App\ViewModel\Database\DecisionReference;
use App\ViewModel\Database\DecisionRow;
use App\ViewModel\Database\ExportCategories;
use App\ViewModel\Database\ExportedDecision;
use App\ViewModel\Database\MeetingView;
use App\ViewModel\Database\RecordedDecision;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_key_exists;
use function array_map;
use function array_values;
use function explode;
use function implode;
use function intval;
use function sprintf;

class Meeting
{
    /**
     * For how many minutes the export is held off after a decision was entered.
     *
     * A meeting is minuted decision by decision, and a member's organ membership is only right again once the last of
     * them is in; syncing in between would publish states that never existed.
     */
    private const int SYNC_PAUSE_AFTER_DECISION = 15;

    /**
     * For how many minutes the export is held off after a decision was deleted, which is longer because deleting one
     * is always part of correcting something.
     */
    private const int SYNC_PAUSE_AFTER_DELETION = 60;

    public function __construct(
        private readonly Annulment $annulmentService,
        private readonly ApiService $apiService,
        private readonly TranslatorInterface $translator,
        private readonly MeetingRepository $meetingRepository,
        private readonly FoundationRepository $foundationRepository,
    ) {
    }

    /**
     * Get a meeting.
     */
    public function getMeeting(
        MeetingTypes $type,
        int $number,
    ): ?MeetingModel {
        return $this->meetingRepository->findMeeting(
            $type,
            $number,
        );
    }

    /**
     * Get a meeting with the decisions taken in it.
     */
    public function getMeetingView(
        MeetingTypes $type,
        int $number,
    ): ?MeetingView {
        $meeting = $this->getMeeting(
            $type,
            $number,
        );

        if (null === $meeting) {
            return null;
        }

        $decisions = [];
        $nextDecisionNumbers = [];

        foreach ($meeting->getDecisions() as $decision) {
            $decisions[] = new DecisionRow(
                $decision->getPoint(),
                $decision->getNumber(),
                // The same join `Decision::getTranslatedContent()` does, in the language being read rather than
                // always in Dutch.
                implode(
                    ' ',
                    array_map(
                        fn (SubDecision $subdecision): string => $subdecision->getContent($this->translator),
                        $decision->getSubdecisions()->toArray(),
                    ),
                ),
                $this->getCopyContent($decision),
                null === $decision->getAnnulledBy()
                    ? null
                    : DecisionReference::fromDecision($decision->getAnnulledBy()->getDecision()),
            );

            $point = $decision->getPoint();
            $next = $decision->getNumber() + 1;

            if (($nextDecisionNumbers[$point] ?? 0) >= $next) {
                continue;
            }

            $nextDecisionNumbers[$point] = $next;
        }

        return new MeetingView(
            $meeting,
            $decisions,
            $nextDecisionNumbers,
        );
    }

    /**
     * Record a new meeting.
     *
     * @return bool whether the meeting was recorded; it is not when it already exists.
     */
    public function createMeeting(MeetingModel $meeting): bool
    {
        // A meeting is identified by its type and number together, so this is what "already exists" means; the form
        // builds a fresh entity either way and cannot tell.
        if (
            null !== $this->getMeeting(
                $meeting->getType(),
                $meeting->getNumber(),
            )
        ) {
            return false;
        }

        $this->meetingRepository->persist($meeting);

        return true;
    }

    /**
     * Check if the decision exists.
     */
    public function decisionExists(
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
    ): bool {
        return null !== $this->meetingRepository->findDecision(
            $type,
            $number,
            $point,
            $decision,
        );
    }

    /**
     * Get what the decision forms let a decision be taken about.
     */
    public function getDecisionOptions(): DecisionOptions
    {
        return new DecisionOptions(
            $this->meetingRepository->findCurrentBoard(),
            $this->meetingRepository->findCurrentBoardNotYetReleased(),
            $this->meetingRepository->findCurrentKeys(),
        );
    }

    /**
     * Record a decision and everything it decides.
     *
     * @throws AnnulmentNotPossible when the decision annuls one that can no longer be taken back.
     */
    public function recordDecision(DecisionModel $decision): RecordedDecision
    {
        $warnings = $this->checkAnnulment($decision);

        $this->meetingRepository->persist($decision->getMeeting());
        $this->apiService->pauseSync(self::SYNC_PAUSE_AFTER_DECISION);

        return new RecordedDecision(
            $decision->getHash(),
            $decision->getMeetingType()->value,
            $decision->getMeetingNumber(),
            $this->getCopyContent($decision),
            array_map(
                fn (AppLanguages $language): string => $decision->getTranslatedContent(
                    $this->translator,
                    $language,
                ),
                AppLanguages::cases(),
            ),
            $warnings,
        );
    }

    /**
     * Delete a decision.
     *
     * @return bool whether there was a decision left to delete; false when another secretary got there first.
     *
     * @throws AnnulmentNotPossible    when deleting an annulment would restore a decision that has since been
     *                                 overtaken.
     * @throws DecisionStillReferenced when later decisions still refer to what this one brought about.
     */
    public function deleteDecision(
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
    ): bool {
        $model = $this->meetingRepository->findDecision(
            $type,
            $number,
            $point,
            $decision,
        );

        if (null === $model) {
            return false;
        }

        // Deleting an annulment restores everything it annulled, so the ledger has to allow that. Checking before the
        // deletion keeps it from failing halfway through.
        foreach ($model->getSubdecisions() as $subdecision) {
            if (!($subdecision instanceof AnnulmentModel)) {
                continue;
            }

            $this->annulmentService->assertAnnulmentCanBeDeleted($subdecision);
        }

        try {
            $this->meetingRepository->deleteDecision(
                $type,
                $number,
                $point,
                $decision,
            );
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new DecisionStillReferenced(
                'This decision is still referred to by another decision.',
                previous: $e,
            );
        }

        $this->apiService->pauseSync(self::SYNC_PAUSE_AFTER_DELETION);

        return true;
    }

    /**
     * Assemble the decision list of the given meetings.
     *
     * @param string[] $meetings as the export form posts them, `<type>-<number>`.
     */
    public function exportDecisions(array $meetings): ExportCategories
    {
        $identifiers = array_map(
            static function (string $meeting): array {
                $meeting = explode(
                    '-',
                    $meeting,
                );

                return [
                    'type' => $meeting[0],
                    'number' => intval($meeting[1]),
                ];
            },
            $meetings,
        );

        $categories = [
            'financial' => [],
            'install' => [],
            'other' => [],
        ];

        foreach ($this->meetingRepository->findDecisionsByMeetings($identifiers) as $decision) {
            $first = $decision->getSubdecisions()->first();

            if (false === $first) {
                continue;
            }

            $categories[$this->getCategory($first)][] = new ExportedDecision(
                $decision->getHash(),
                $decision->getMeeting()->getDate(),
                $decision->getContent(
                    $this->translator,
                    true,
                ),
            );
        }

        return new ExportCategories(
            $categories['financial'],
            $categories['install'],
            $categories['other'],
        );
    }

    /**
     * Search for organs by name.
     *
     * An organ is the decision that founded it, and it is only an organ for as long as that decision stands.
     *
     * @return FoundationModel[]
     */
    public function findOrgans(string $query): array
    {
        return $this->foundationRepository->organSearch($query);
    }

    /**
     * Search for organs by name.
     *
     * @return array<array-key, array<string, mixed>>
     */
    public function searchOrgans(string $query): array
    {
        return array_map(
            static fn (FoundationModel $foundation): array => $foundation->toArray(),
            $this->findOrgans($query),
        );
    }

    /**
     * Search for decisions by name.
     *
     * @return array<array-key, array<string, mixed>>
     */
    public function searchDecisions(
        string $query,
        ?MeetingTypes $meetingType = null,
        ?int $meetingNumber = null,
        ?int $point = null,
        ?int $number = null,
    ): array {
        $before = null;

        if (
            null !== $meetingType
            && null !== $meetingNumber
        ) {
            // Only decisions taken before the one being entered can be annulled by it.
            $before = $this->meetingRepository->findMeeting(
                $meetingType,
                $meetingNumber,
            );
        }

        return array_map(
            fn (DecisionModel $decision): array => $decision->toArray($this->translator),
            $this->meetingRepository->searchDecision(
                $query,
                false,
                $before,
                $point,
                $number,
            ),
        );
    }

    /**
     * Search for meetings by name.
     *
     * @return array<array-key, array<string, mixed>>
     */
    public function searchMeetings(string $query): array
    {
        return array_map(
            static fn (MeetingModel $meeting): array => $meeting->toArray(),
            $this->meetingRepository->searchMeeting($query),
        );
    }

    /**
     * The foundation subdecision that created an organ, which is how an organ is addressed.
     */
    public function findFoundation(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
        int $sequence,
    ): ?Foundation {
        return $this->foundationRepository->findOrgan(
            $meetingType,
            $meetingNumber,
            $decisionPoint,
            $decisionNumber,
            $sequence,
        );
    }

    /**
     * Get an organ and everyone currently installed in it.
     *
     * @return array<string, mixed>|null
     */
    public function getOrganInfo(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
        int $sequence,
    ): ?array {
        $foundation = $this->foundationRepository->findOrgan(
            $meetingType,
            $meetingNumber,
            $decisionPoint,
            $decisionNumber,
            $sequence,
        );

        if (null === $foundation) {
            return null;
        }

        $members = [];

        foreach ($foundation->getReferences() as $reference) {
            if (!($reference instanceof InstallationModel)) {
                continue;
            }

            $member = $reference->getMember();
            $lidnr = $member->getLidnr();

            if (
                !array_key_exists(
                    $lidnr,
                    $members,
                )
            ) {
                // Only what it takes to say who this is: the page shows a name and hangs mutations off a membership
                // number.
                $members[$lidnr] = [
                    'member' => [
                        'lidnr' => $lidnr,
                        'fullName' => $member->getFullName(),
                    ],
                    'installations' => [],
                ];
            }

            $members[$lidnr]['installations'][] = [
                'meeting_type' => $reference->getMeetingType(),
                'meeting_number' => $reference->getMeetingNumber(),
                'decision_point' => $reference->getDecisionPoint(),
                'decision_number' => $reference->getDecisionNumber(),
                'subdecision_sequence' => $reference->getSequence(),
                'function' => $reference->getFunction(),
                'functionName' => $reference->getFunction()->trans($this->translator),
            ];
        }

        $data = $foundation->toArray();
        $data['members'] = array_values($members);

        return $data;
    }

    /**
     * Check that what a decision annuls can actually be annulled, if it annuls anything at all.
     *
     * The decision search already leaves out the decisions that cannot be annulled, but the form posts the target as
     * plain identifiers, so it has to be checked again here.
     *
     * @return string[] what is worth pointing out about the annulment.
     *
     * @throws AnnulmentNotPossible
     */
    private function checkAnnulment(DecisionModel $decision): array
    {
        $annulment = null;

        foreach ($decision->getSubdecisions() as $subdecision) {
            if (!($subdecision instanceof AnnulmentModel)) {
                continue;
            }

            $annulment = $subdecision;
        }

        if (null === $annulment) {
            return [];
        }

        $target = $annulment->getTarget();

        try {
            if (
                $target->getMeetingType() === $decision->getMeetingType()
                && $target->getMeetingNumber() === $decision->getMeetingNumber()
                && $target->getPoint() === $decision->getPoint()
                && $target->getNumber() === $decision->getNumber()
            ) {
                throw new AnnulmentNotPossible($this->translator->trans('A decision cannot annul itself.'));
            }

            if ($target->isAnnulled()) {
                throw new AnnulmentNotPossible($this->translator->trans('This decision has already been annulled.'));
            }

            if (
                !$this->annulmentService->isBefore(
                    $target,
                    $decision,
                )
            ) {
                // The ledger cannot be rewritten from the past: whatever is annulled must already have happened.
                throw new AnnulmentNotPossible(
                    $this->translator->trans('A decision can only annul a decision taken before it.'),
                );
            }

            if ($this->annulmentService->isAnnulling($target)) {
                // Annulling an annulment has no well-defined meaning: an annulment has no effects of its own that can
                // be reverted. Delete the annulling decision instead, that restores what it annulled.
                throw new AnnulmentNotPossible(
                    $this->translator->trans('An annulling decision cannot be annulled, delete it instead.'),
                );
            }

            // Finally, the ledger must allow the decision to be taken back at all.
            return $this->annulmentService->assertDecisionCanBeAnnulled($target);
        } catch (AnnulmentNotPossible $e) {
            // Building the decision already attached it to its meeting, which cascades persists; a decision that is
            // turned down has to be taken back out again.
            $decision->getMeeting()->removeDecision($decision);

            throw $e;
        }
    }

    /**
     * The section of the decision list a decision belongs in, which is what it decides about.
     *
     * @return 'financial'|'install'|'other'
     */
    private function getCategory(SubDecision $subdecision): string
    {
        return match (true) {
            // Statement extends Budget, so it arrives here too.
            $subdecision instanceof Budget => 'financial',
            $subdecision instanceof FoundationModel,
            $subdecision instanceof Abrogation,
            $subdecision instanceof InstallationModel,
            $subdecision instanceof DischargeModel,
            $subdecision instanceof BoardInstallationModel,
            $subdecision instanceof BoardDischargeModel => 'install',
            default => 'other',
        };
    }

    /**
     * The decision in the form the decision list is written in, ready to be pasted into it.
     */
    private function getCopyContent(DecisionModel $decision): string
    {
        return sprintf(
            '\decision[%s]{%s}',
            $decision->getTranslatedContent(
                $this->translator,
                AppLanguages::English,
                true,
            ),
            $decision->getTranslatedContent(
                $this->translator,
                AppLanguages::Dutch,
                true,
            ),
        );
    }
}
