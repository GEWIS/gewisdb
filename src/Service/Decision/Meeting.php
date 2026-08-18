<?php

declare(strict_types=1);

namespace App\Service\Decision;

use App\Entity\Decision\Decision as DecisionModel;
use App\Entity\Decision\Enums\InstallationFunctions;
use App\Entity\Decision\Enums\MeetingTypes;
use App\Entity\Decision\Enums\OrganTypes;
use App\Entity\Decision\Meeting as MeetingModel;
use App\Entity\Decision\SubDecision\Annulment as AnnulmentModel;
use App\Entity\Decision\SubDecision\Board\Installation as BoardInstallationModel;
use App\Entity\Decision\SubDecision\Discharge as DischargeModel;
use App\Entity\Decision\SubDecision\Foundation as FoundationModel;
use App\Entity\Decision\SubDecision\Installation as InstallationModel;
use App\Entity\Decision\SubDecision\Key\Granting as KeyGrantingModel;
use App\Entity\Decision\SubDecision\Reappointment as ReappointmentModel;
use App\Exception\Decision\AnnulmentNotPossible;
use App\Form\Decision\AbolishType;
use App\Form\Decision\AnnulmentType;
use App\Form\Decision\Board\DischargeType as BoardDischargeType;
use App\Form\Decision\Board\InstallType as BoardInstallType;
use App\Form\Decision\Board\ReleaseType as BoardReleaseType;
use App\Form\Decision\BudgetType;
use App\Form\Decision\CreateMeetingType;
use App\Form\Decision\DeleteDecisionType;
use App\Form\Decision\FoundationType;
use App\Form\Decision\InstallType;
use App\Form\Decision\Key\GrantType as KeyGrantType;
use App\Form\Decision\Key\WithdrawType as KeyWithdrawType;
use App\Form\Decision\MinutesType;
use App\Form\Decision\OrganRegulationType;
use App\Form\Decision\OtherType;
use App\Form\Query\ExportType;
use App\Repository\Decision\MeetingRepository;
use App\Repository\Decision\SubDecision\FoundationRepository;
use App\Repository\Member\MemberRepository;
use Symfony\Component\Form\FormError;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

use function array_walk;
use function explode;
use function in_array;
use function intval;
use function sprintf;

class Meeting
{
    public function __construct(
        private readonly Annulment $annulmentService,
        private readonly TranslatorInterface $translator,
        private readonly FormFactoryInterface $formFactory,
        private readonly MeetingRepository $meetingRepository,
        private readonly MemberRepository $memberRepository,
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
        return $this->meetingRepository->findMeeting($type, $number);
    }

    /**
     * Get all meetings.
     *
     * TODO: pagination
     *
     * @return array<array-key, array{0: MeetingModel, 1: int}>
     */
    public function getAllMeetings(): array
    {
        return $this->meetingRepository->findAllWithDecisionCount();
    }

    /**
     * Find decisions by meetings.
     *
     * @param array<array-key, array{type: string, number: int}> $meetings
     *
     * @return DecisionModel[]
     */
    public function getDecisionsByMeetings(array $meetings): array
    {
        return $this->meetingRepository->findDecisionsByMeetings($meetings);
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
        return null !== $this->meetingRepository->findDecision($type, $number, $point, $decision);
    }

    /**
     * Get the current board installations.
     *
     * @return BoardInstallationModel[]
     */
    public function getCurrentBoard(): array
    {
        return $this->meetingRepository->findCurrentBoard();
    }

    /**
     * Get the current board installations, but without board members who have already been released.
     *
     * @return BoardInstallationModel[]
     */
    public function getCurrentBoardNotYetReleased(): array
    {
        return $this->meetingRepository->findCurrentBoardNotYetReleased();
    }

    /**
     * @return KeyGrantingModel[]
     */
    public function getCurrentKeys(): array
    {
        return $this->meetingRepository->findCurrentKeys();
    }

    /**
     * Export decisions.
     *
     * @return DecisionModel[]|null
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function export(array $data): ?array
    {
        $form = $this->createExportForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return null;
        }

        // extract the meetings
        $data = $form->getData();
        $meetings = [];
        foreach ($data['meetings'] as $meeting) {
            $meeting = explode('-', $meeting);
            $meetings[] = [
                'type' => $meeting[0],
                'number' => intval($meeting[1]),
            ];
        }

        // find meeting data
        return $this->getDecisionsByMeetings($meetings);
    }

    /**
     * Annul decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     *     warnings: string[],
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function annulDecision(array $data): array
    {
        $form = $this->createAnnulmentForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'annulment',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $check = $this->checkAnnulmentTarget($decision);

        if (null !== $check['error']) {
            // Building the decision already attached it to its meeting, which cascades persists; a decision that is
            // turned down has to be taken back out again.
            $decision->getMeeting()->removeDecision($decision);
            $form->get('name')->addError(new FormError($check['error']));

            return [
                'type' => 'annulment',
                'form' => $form,
            ];
        }

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'annulment',
            'decision' => $decision,
            'warnings' => $check['warnings'],
        ];
    }

    /**
     * Check that the decision targeted by an annulment can actually be annulled.
     *
     * The decision search already leaves out the decisions that cannot be annulled, but the form posts the target as
     * plain identifiers, so it has to be checked again here.
     *
     * @return array{error: string|null, warnings: string[]} why the target cannot be annulled, or what is worth
     *                                                        pointing out about annulling it.
     */
    private function checkAnnulmentTarget(DecisionModel $decision): array
    {
        /** @var AnnulmentModel $annulment */
        $annulment = $decision->getSubdecisions()->first();
        $reference = $annulment->getTarget();

        $target = $this->meetingRepository->findDecision(
            $reference->getMeetingType(),
            $reference->getMeetingNumber(),
            $reference->getPoint(),
            $reference->getNumber(),
        );

        if (null === $target) {
            return $this->error($this->translator->trans('This decision does not exist.'));
        }

        if (
            $target->getMeetingType() === $decision->getMeetingType()
            && $target->getMeetingNumber() === $decision->getMeetingNumber()
            && $target->getPoint() === $decision->getPoint()
            && $target->getNumber() === $decision->getNumber()
        ) {
            return $this->error($this->translator->trans('A decision cannot annul itself.'));
        }

        if ($target->isAnnulled()) {
            return $this->error($this->translator->trans('This decision has already been annulled.'));
        }

        if (!$this->annulmentService->isBefore($target, $decision)) {
            // The ledger cannot be rewritten from the past: whatever is annulled must already have happened.
            return $this->error($this->translator->trans('A decision can only annul a decision taken before it.'));
        }

        if ($this->annulmentService->isAnnulling($target)) {
            // Annulling an annulment has no well-defined meaning: an annulment has no effects of its own that can be
            // reverted. Delete the annulling decision instead, that restores what it annulled.
            return $this->error($this->translator->trans(
                'An annulling decision cannot be annulled, delete it instead.',
            ));
        }

        // Finally, the ledger must allow the decision to be taken back at all.
        try {
            $warnings = $this->annulmentService->assertDecisionCanBeAnnulled($target);
        } catch (AnnulmentNotPossible $e) {
            return $this->error($e->getMessage());
        }

        return [
            'error' => null,
            'warnings' => $warnings,
        ];
    }

    /**
     * @return array{error: string, warnings: string[]}
     */
    private function error(string $message): array
    {
        return [
            'error' => $message,
            'warnings' => [],
        ];
    }

    /**
     * Delete a decision.
     *
     * @throws AnnulmentNotPossible when deleting an annulment would restore a decision that has since been overtaken.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function deleteDecision(
        array $data,
        MeetingTypes $type,
        int $number,
        int $point,
        int $decision,
    ): bool {
        $form = $this->createDeleteDecisionForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return false;
        }

        $model = $this->meetingRepository->findDecision($type, $number, $point, $decision);

        if (null !== $model) {
            // Deleting an annulment restores everything it annulled, so the ledger has to allow that. Checking before
            // the deletion keeps it from failing halfway through.
            foreach ($model->getSubdecisions() as $subdecision) {
                if (!($subdecision instanceof AnnulmentModel)) {
                    continue;
                }

                $this->annulmentService->assertAnnulmentCanBeDeleted($subdecision);
            }
        }

        $this->meetingRepository->deleteDecision($type, $number, $point, $decision);

        return true;
    }

    /**
     * Other decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function otherDecision(array $data): array
    {
        $form = $this->createOtherForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'other',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'other',
            'decision' => $decision,
        ];
    }

    /**
     * Abolish decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function abolishDecision(array $data): array
    {
        $form = $this->createAbolishForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'abolish',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'foundation',
            'decision' => $decision,
        ];
    }

    /**
     * Board install decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function boardInstallDecision(array $data): array
    {
        $form = $this->createBoardInstallForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'board_install',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'board_install',
            'decision' => $decision,
        ];
    }

    /**
     * Board discharge decision.
     *
     * @return array{
     *     type: string,
     *     installs: BoardInstallationModel[],
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function boardDischargeDecision(array $data): array
    {
        $form = $this->createBoardDischargeForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'board_discharge',
                'installs' => $this->getCurrentBoard(),
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'board_discharge',
            'decision' => $decision,
        ];
    }

    /**
     * Board release decision.
     *
     * @return array{
     *     type: string,
     *     installs_filtered: BoardInstallationModel[],
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function boardReleaseDecision(array $data): array
    {
        $form = $this->createBoardReleaseForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'board_release',
                'installs_filtered' => $this->getCurrentBoardNotYetReleased(),
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'board_release',
            'decision' => $decision,
        ];
    }

    /**
     * Key code granting decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function keyGrantDecision(array $data): array
    {
        $form = $this->createKeyGrantForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'key_grant',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'key_grant',
            'decision' => $decision,
        ];
    }

    /**
     * Key code withdrawal decision.
     *
     * @return array{
     *     type: string,
     *     grants: KeyGrantingModel[],
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function keyWithdrawDecision(array $data): array
    {
        $form = $this->createKeyWithdrawForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'key_withdraw',
                'grants' => $this->getCurrentKeys(),
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'key_withdraw',
            'decision' => $decision,
        ];
    }

    /**
     * Install decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function installDecision(array $data): array
    {
        $form = $this->createInstallForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'install',
                'form' => $form,
            ];
        }

        // The install form is not bound to a decision: the meeting, the organ, and everyone involved are posted as
        // plain identifiers. They are resolved here, and the decision is assembled from them below. Anything that
        // cannot be resolved makes the form the result again, so it is shown to whoever entered it.
        /** @var array $decision */
        $decision = $form->getData();
        $meeting = $this->getMeeting(
            MeetingTypes::from($decision['meeting']['type']),
            intval($decision['meeting']['number']),
        );
        $subdecision = $this->foundationRepository->findSimple(
            MeetingTypes::from($decision['subdecision']['meeting_type']),
            intval($decision['subdecision']['meeting_number']),
            intval($decision['subdecision']['decision_point']),
            intval($decision['subdecision']['decision_number']),
            intval($decision['subdecision']['sequence']),
        );

        if (
            null === $meeting
            || null === $subdecision
        ) {
            return [
                'type' => 'install',
                'form' => $form,
            ];
        }

        $decision['meeting'] = $meeting;
        $decision['subdecision'] = $subdecision;

        // Prepare installations.
        $installations = [];
        array_walk($decision['installations'], function ($value) use (&$installations): void {
            $member = $this->memberRepository->findSimple(intval($value['member']['lidnr']));

            if (null === $member) {
                return;
            }

            $installations[] = [
                'member' => $member,
                'function' => $value['function'],
            ];
        });
        $decision['installations'] = $installations;

        // Prepare reappointments and discharges.
        foreach (['reappointments', 'discharges'] as $subDecisionType) {
            $subDecisions = [];
            array_walk($decision[$subDecisionType], function ($value) use (&$subDecisions): void {
                $decision = $this->foundationRepository->findInstallationDecision(
                    MeetingTypes::from($value['meeting_type']),
                    intval($value['meeting_number']),
                    intval($value['decision_point']),
                    intval($value['decision_number']),
                    intval($value['sequence']),
                );

                if (null === $decision) {
                    return;
                }

                $subDecisions[] = $decision;
            });
            $decision[$subDecisionType] = $subDecisions;
        }

        $decision = $this->buildInstallDecision($decision);

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'install',
            'decision' => $decision,
        ];
    }

    /**
     * Foundation decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function foundationDecision(array $data): array
    {
        $form = $this->createFoundationForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'foundation',
                'form' => $form,
            ];
        }

        // As with the install form, this one is not bound to a decision; see installDecision().
        /** @var array $decision */
        $decision = $form->getData();
        $meeting = $this->getMeeting(
            MeetingTypes::from($decision['meeting']['type']),
            (int) $decision['meeting']['number'],
        );

        if (null === $meeting) {
            return [
                'type' => 'foundation',
                'form' => $form,
            ];
        }

        $decision['meeting'] = $meeting;

        $members = [];
        array_walk($decision['members'], function ($value) use (&$members): void {
            $member = $this->memberRepository->findSimple((int) $value['member']['lidnr']);

            if (null === $member) {
                return;
            }

            $members[] = [
                'member' => $member,
                'function' => $value['function'],
            ];
        });

        $decision['members'] = $members;
        $decision = $this->buildFoundationDecision($decision);

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'foundation',
            'decision' => $decision,
        ];
    }

    /**
     * Budget decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function budgetDecision(array $data): array
    {
        $form = $this->createBudgetForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'budget',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'budget',
            'decision' => $decision,
        ];
    }

    /**
     * Minutes decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function minutesDecision(array $data): array
    {
        $form = $this->createMinutesForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'minutes',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'minutes',
            'decision' => $decision,
        ];
    }

    /**
     * Organ regulation decision.
     *
     * @return array{
     *     type: string,
     *     form: FormInterface,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function regulationDecision(array $data): array
    {
        $form = $this->createRegulationForm();

        $form->submit($data);

        if (!$form->isValid()) {
            return [
                'type' => 'organ_regulation',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        $this->meetingRepository->persist($decision->getMeeting());

        return [
            'type' => 'organ_regulation',
            'decision' => $decision,
        ];
    }

    /**
     * Create a meeting.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function createMeeting(array $data): ?MeetingModel
    {
        $form = $this->createMeetingForm();
        $form->submit($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var MeetingModel $meeting */
        $meeting = $form->getData();

        if ($this->meetingRepository->isManaged($meeting)) {
            // meeting is already in the database
            $form->get('number')->addError(new FormError('Deze vergadering bestaat al'));

            return null;
        }

        $this->meetingRepository->persist($meeting);

        return $meeting;
    }

    /**
     * Search for organs by name.
     *
     * @return FoundationModel[]
     */
    public function organSearch(string $query): array
    {
        return $this->foundationRepository->organSearch($query);
    }

    /**
     * Search for decisions by name.
     *
     * @return DecisionModel[]
     */
    public function decisionSearch(
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
            $before = $this->meetingRepository->findMeeting($meetingType, $meetingNumber);
        }

        return $this->meetingRepository->searchDecision($query, false, $before, $point, $number);
    }

    /**
     * Search for meetings by name.
     *
     * @return MeetingModel[]
     */
    public function meetingSearch(string $query): array
    {
        return $this->meetingRepository->searchMeeting($query);
    }

    /**
     * Get the foundation of an organ.
     */
    public function findFoundation(
        MeetingTypes $meetingType,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
        int $sequence,
    ): ?FoundationModel {
        return $this->foundationRepository->findOrgan(
            $meetingType,
            $meetingNumber,
            $decisionPoint,
            $decisionNumber,
            $sequence,
        );
    }

    /**
     * Build a decision from the meeting and the position within it that a decision form posts.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function buildDecision(array $data): DecisionModel
    {
        $decision = new DecisionModel();

        $decision->setMeeting($data['meeting']);
        $decision->setPoint(intval($data['point']));
        $decision->setNumber(intval($data['decision']));

        return $decision;
    }

    /**
     * Build an install decision and its subdecisions from the resolved install form data.
     *
     * Reappointments come first, then discharges, then the installations, and the sequence numbers follow that order.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function buildInstallDecision(array $data): DecisionModel
    {
        $decision = $this->buildDecision($data);
        $foundation = $data['subdecision'];

        $num = 1;

        if (!empty($data['reappointments'])) {
            foreach ($data['reappointments'] as $install) {
                $reappointment = new ReappointmentModel();
                $reappointment->setInstallation($install);
                $reappointment->setSequence($num++);
                $reappointment->setDecision($decision);
            }
        }

        if (!empty($data['discharges'])) {
            foreach ($data['discharges'] as $install) {
                $discharge = new DischargeModel();
                $discharge->setInstallation($install);
                $discharge->setSequence($num++);
                $discharge->setDecision($decision);
            }
        }

        if (!empty($data['installations'])) {
            foreach ($data['installations'] as $install) {
                if (!($install['function'] instanceof InstallationFunctions)) {
                    $install['function'] = InstallationFunctions::from($install['function']);
                }

                $installation = new InstallationModel();
                $installation->setSequence($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction($install['function']);
                $installation->setMember($install['member']);
                $installation->setDecision($decision);
            }
        }

        return $decision;
    }

    /**
     * Build a foundation decision, the organ it founds, and the installations into it.
     *
     * A voting committee is named after the meeting that installs it, so its name and abbreviation are derived rather
     * than entered, and what was entered becomes its purpose.
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    private function buildFoundationDecision(array $data): DecisionModel
    {
        $decision = $this->buildDecision($data);

        $foundation = new FoundationModel();

        $foundation->setSequence(1);

        if (!($data['type'] instanceof OrganTypes)) {
            $data['type'] = OrganTypes::from($data['type']);
        }

        $foundation->setOrganType($data['type']);
        $foundation->setDecision($decision);

        if (OrganTypes::SC !== $foundation->getOrganType()) {
            $foundation->setName($data['name']);
            $foundation->setAbbr($data['abbr']);
        } else {
            $foundation->setName(sprintf(
                'Stemcommissie voor %s van de %de ALV',
                $data['name'],
                $foundation->getMeetingNumber(),
            ));
            $foundation->setAbbr(sprintf(
                'SC%d-%s',
                $foundation->getMeetingNumber(),
                $data['abbr'],
            ));
            $foundation->setPurpose($data['name']);
        }

        $num = 2;

        $addedMembers = [];

        foreach ($data['members'] as $install) {
            if (!($install['function'] instanceof InstallationFunctions)) {
                $install['function'] = InstallationFunctions::from($install['function']);
            }

            // Holding a function in an organ means being one of its (active) members, so anyone installed with a
            // function is installed as a member as well.
            if (
                InstallationFunctions::Member !== $install['function']
                && InstallationFunctions::InactiveMember !== $install['function']
                && !in_array($install['member']->getLidnr(), $addedMembers, true)
            ) {
                $installation = new InstallationModel();
                $installation->setSequence($num++);
                $installation->setFoundation($foundation);
                $installation->setFunction(InstallationFunctions::Member);
                $installation->setMember($install['member']);
                $installation->setDecision($decision);
                $addedMembers[] = $install['member']->getLidnr();
            }

            $installation = new InstallationModel();
            $installation->setSequence($num++);
            $installation->setFoundation($foundation);
            $installation->setFunction($install['function']);
            $installation->setMember($install['member']);
            $installation->setDecision($decision);
        }

        return $decision;
    }

    /**
     * Get the create meeting form.
     */
    public function createMeetingForm(): FormInterface
    {
        return $this->formFactory->create(CreateMeetingType::class, new MeetingModel());
    }

    /**
     * Get the delete decision form.
     */
    public function createDeleteDecisionForm(): FormInterface
    {
        return $this->formFactory->create(DeleteDecisionType::class);
    }

    /**
     * Get the board install form.
     */
    public function createBoardInstallForm(): FormInterface
    {
        return $this->formFactory->create(BoardInstallType::class, new DecisionModel());
    }

    /**
     * Get the board release form.
     */
    public function createBoardReleaseForm(): FormInterface
    {
        return $this->formFactory->create(BoardReleaseType::class, new DecisionModel());
    }

    /**
     * Get the board discharge form.
     */
    public function createBoardDischargeForm(): FormInterface
    {
        return $this->formFactory->create(BoardDischargeType::class, new DecisionModel());
    }

    /**
     * Get install form.
     *
     * Deliberately not given a decision to build: see installDecision().
     */
    public function createInstallForm(): FormInterface
    {
        return $this->formFactory->create(InstallType::class);
    }

    /**
     * Get abolish form.
     */
    public function createAbolishForm(): FormInterface
    {
        return $this->formFactory->create(AbolishType::class, new DecisionModel());
    }

    /**
     * Get the annulment form.
     */
    public function createAnnulmentForm(): FormInterface
    {
        return $this->formFactory->create(AnnulmentType::class, new DecisionModel());
    }

    /**
     * Get regulation form.
     */
    public function createRegulationForm(): FormInterface
    {
        return $this->formFactory->create(OrganRegulationType::class, new DecisionModel());
    }

    /**
     * Get foundation form.
     *
     * Deliberately not given a decision to build: see foundationDecision().
     */
    public function createFoundationForm(): FormInterface
    {
        return $this->formFactory->create(FoundationType::class);
    }

    /**
     * Get budget form.
     */
    public function createBudgetForm(): FormInterface
    {
        return $this->formFactory->create(BudgetType::class, new DecisionModel());
    }

    /**
     * Get key grant form.
     */
    public function createKeyGrantForm(): FormInterface
    {
        return $this->formFactory->create(KeyGrantType::class, new DecisionModel());
    }

    /**
     * Get key withdraw form.
     */
    public function createKeyWithdrawForm(): FormInterface
    {
        return $this->formFactory->create(KeyWithdrawType::class, new DecisionModel());
    }

    /**
     * Get minutes form.
     */
    public function createMinutesForm(): FormInterface
    {
        return $this->formFactory->create(MinutesType::class, new DecisionModel());
    }

    /**
     * Get other form.
     */
    public function createOtherForm(): FormInterface
    {
        return $this->formFactory->create(OtherType::class, new DecisionModel());
    }

    /**
     * Get the export form.
     */
    public function createExportForm(): FormInterface
    {
        return $this->formFactory->create(ExportType::class);
    }
}
