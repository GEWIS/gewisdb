<?php

declare(strict_types=1);

namespace Database\Service;

use Application\Model\Enums\MeetingTypes;
use Database\Form\Abolish as AbolishForm;
use Database\Form\Board\Discharge as BoardDischargeForm;
use Database\Form\Board\Install as BoardInstallForm;
use Database\Form\Board\Release as BoardReleaseForm;
use Database\Form\Budget as BudgetForm;
use Database\Form\CreateMeeting as CreateMeetingForm;
use Database\Form\DeleteDecision as DeleteDecisionForm;
use Database\Form\Destroy as DestroyForm;
use Database\Form\Export as ExportForm;
use Database\Form\Foundation as FoundationForm;
use Database\Form\Install as InstallForm;
use Database\Form\Key\Grant as KeyGrantForm;
use Database\Form\Key\Withdraw as KeyWithdrawForm;
use Database\Form\Other as OtherForm;
use Database\Hydrator\Foundation as FoundationHydrator;
use Database\Hydrator\Install as InstallHydrator;
use Database\Mapper\Meeting as MeetingMapper;
use Database\Mapper\Member as MemberMapper;
use Database\Mapper\Organ as OrganMapper;
use Database\Model\Decision as DecisionModel;
use Database\Model\Meeting as MeetingModel;
use Database\Model\SubDecision\Board\Installation as BoardInstallationModel;
use Database\Model\SubDecision\Foundation as FoundationModel;
use Database\Model\SubDecision\Key\Granting as KeyGrantingModel;
use Laminas\Stdlib\PriorityQueue;
use ReflectionObject;

use function array_walk;
use function explode;
use function intval;

class Meeting
{
    public function __construct(
        private readonly AbolishForm $abolishForm,
        private readonly BoardDischargeForm $boardDischargeForm,
        private readonly BoardInstallForm $boardInstallForm,
        private readonly BoardReleaseForm $boardReleaseForm,
        private readonly BudgetForm $budgetForm,
        private readonly CreateMeetingForm $createMeetingForm,
        private readonly DeleteDecisionForm $deleteDecisionForm,
        private readonly DestroyForm $destroyForm,
        private readonly ExportForm $exportForm,
        private readonly FoundationForm $foundationForm,
        private readonly InstallForm $installForm,
        private readonly KeyGrantForm $keyGrantForm,
        private readonly KeyWithdrawForm $keyWithdrawForm,
        private readonly OtherForm $otherForm,
        private readonly MeetingMapper $meetingMapper,
        private readonly MemberMapper $memberMapper,
        private readonly OrganMapper $organMapper,
    ) {
    }

    /**
     * Get a meeting.
     */
    public function getMeeting(
        MeetingTypes $type,
        int $number,
    ): ?MeetingModel {
        return $this->getMeetingMapper()->find($type, $number);
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
        return $this->getMeetingMapper()->findAll();
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
        return $this->getMeetingMapper()->findDecisionsByMeetings($meetings);
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
        $mapper = $this->getMeetingMapper();

        return null !== $mapper->findDecision($type, $number, $point, $decision);
    }

    /**
     * Get the current board installations.
     *
     * @return BoardInstallationModel[]
     */
    public function getCurrentBoard(): array
    {
        return $this->getMeetingMapper()->findCurrentBoard();
    }

    /**
     * Get the current board installations, but without board members who have already been released.
     *
     * @return BoardInstallationModel[]
     */
    public function getCurrentBoardNotYetReleased(): array
    {
        return $this->getMeetingMapper()->findCurrentBoardNotYetReleased();
    }

    /**
     * @return KeyGrantingModel[]
     */
    public function getCurrentKeys(): array
    {
        return $this->getMeetingMapper()->findCurrentKeys();
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
        $form = $this->getExportForm();

        $form->setData($data);

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
     * Destroy decision.
     *
     * @return array{
     *     type: string,
     *     form: DestroyForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function destroyDecision(array $data): array
    {
        $form = $this->getDestroyForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'destroy',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return [
            'type' => 'destroy',
            'decision' => $decision,
        ];
    }

    /**
     * Delete a decision.
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
        $form = $this->getDeleteDecisionForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return false;
        }

        $mapper = $this->getMeetingMapper();

        $mapper->deleteDecision($type, $number, $point, $decision);

        return true;
    }

    /**
     * Other decision.
     *
     * @return array{
     *     type: string,
     *     form: OtherForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function otherDecision(array $data): array
    {
        $form = $this->getOtherForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'other',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: AbolishForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function abolishDecision(array $data): array
    {
        $form = $this->getAbolishForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'abolish',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: BoardInstallForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function boardInstallDecision(array $data): array
    {
        $form = $this->getBoardInstallForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'board_install',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: BoardDischargeForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function boardDischargeDecision(array $data): array
    {
        $form = $this->getBoardDischargeForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'board_discharge',
                'installs' => $this->getCurrentBoard(),
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: BoardReleaseForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function boardReleaseDecision(array $data): array
    {
        $form = $this->getBoardReleaseForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'board_release',
                'installs_filtered' => $this->getCurrentBoardNotYetReleased(),
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: KeyGrantForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function keyGrantDecision(array $data): array
    {
        $form = $this->getKeyGrantForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'key_grant',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: KeyWithdrawForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function keyWithdrawDecision(array $data): array
    {
        $form = $this->getKeyWithdrawForm();

        $form->setData($data);
        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'key_withdraw',
                'grants' => $this->getCurrentKeys(),
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: InstallForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function installDecision(array $data): array
    {
        $form = $this->getInstallForm();

        $form->setData($data);
        // IMPORTANT:
        // The following line has been disabled to fix an issue with the validation of the member function fieldset in
        // the installation form. For some reason, when binding a decision, the fieldset loses its data, which prevents
        // proper validation of the data. We can bypass this by regularly checking the form and using the hydrator
        // afterwards to create the actual entities.
        // $form->bind(new Decision());

        if (!$form->isValid()) {
            return [
                'type' => 'install',
                'form' => $form,
            ];
        }

        // See important note above, this does not return an object. Because we are not doing this the normal way we
        // must ensure that the meeting actually exists.
        /** @var array $decision */
        $decision = $form->getData();
        $meeting = $this->getMeeting(
            MeetingTypes::from($decision['meeting']['type']),
            (int) $decision['meeting']['number'],
        );
        $subdecision = $this->getOrganMapper()->findSimple(
            MeetingTypes::from($decision['subdecision']['meeting_type']),
            (int) $decision['subdecision']['meeting_number'],
            (int) $decision['subdecision']['decision_point'],
            (int) $decision['subdecision']['decision_number'],
            (int) $decision['subdecision']['number'],
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

        $installations = [];
        array_walk($decision['installations'], function ($value) use (&$installations): void {
            $member = $this->memberMapper->findSimple((int) $value['member']['lidnr']);

            if (null === $member) {
                return;
            }

            $installations[] = [
                'member' => $member,
                'function' => $value['function'],
            ];
        });

        $decision['installations'] = $installations;

        $discharges = [];
        array_walk($decision['discharges'], function ($value) use (&$discharges): void {
            $decision = $this->getOrganMapper()->findInstallationDecision(
                MeetingTypes::from($value['meeting_type']),
                (int) $value['meeting_number'],
                (int) $value['decision_point'],
                (int) $value['decision_number'],
                (int) $value['number'],
            );

            if (null === $decision) {
                return;
            }

            $discharges[] = $decision;
        });

        $decision['discharges'] = $discharges;

        $decision = (new InstallHydrator())->hydrate($decision, new DecisionModel());

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: FoundationForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function foundationDecision(array $data): array
    {
        $form = $this->getFoundationForm();

        $form->setData($data);
        // IMPORTANT:
        // The following line has been disabled to fix an issue with the validation of the member function fieldset in
        // the foundation form. For some reason, when binding a decision, the fieldset loses its data, which prevents
        // proper validation of the data. We can bypass this by regularly checking the form and using the hydrator
        // afterwards to create the actual entities.
        // $form->bind(new Decision());

        if (!$form->isValid()) {
            return [
                'type' => 'foundation',
                'form' => $form,
            ];
        }

        // See important note above, this does not return an object. Because we are not doing this the normal way we
        // must ensure that the meeting actually exists.
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
            $member = $this->memberMapper->findSimple((int) $value['member']['lidnr']);

            if (null === $member) {
                return;
            }

            $members[] = [
                'member' => $member,
                'function' => $value['function'],
            ];
        });

        $decision['members'] = $members;
        $decision = (new FoundationHydrator())->hydrate($decision, new DecisionModel());

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

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
     *     form: BudgetForm,
     * }|array{
     *     type: string,
     *     decision: DecisionModel,
     * }
     *
     * @phpcsSuppress SlevomatCodingStandard.TypeHints.ParameterTypeHint.MissingTraversableTypeHintSpecification
     */
    public function budgetDecision(array $data): array
    {
        $form = $this->getBudgetForm();

        // use hack to make sure we do not have validators for these fields
        $approveChain = $form->getInputFilter()->get('approve')->getValidatorChain();
        $refObj = new ReflectionObject($approveChain);
        $refProp = $refObj->getProperty('validators');
        $refProp->setValue($approveChain, new PriorityQueue());

        $changesChain = $form->getInputFilter()->get('changes')->getValidatorChain();
        $refObj = new ReflectionObject($changesChain);
        $refProp = $refObj->getProperty('validators');
        $refProp->setValue($changesChain, new PriorityQueue());

        $form->setData($data);

        $form->bind(new DecisionModel());

        if (!$form->isValid()) {
            return [
                'type' => 'budget',
                'form' => $form,
            ];
        }

        /** @var DecisionModel $decision */
        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return [
            'type' => 'budget',
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
        $form = $this->getCreateMeetingForm();
        $form->bind(new MeetingModel());
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        /** @var MeetingModel $meeting */
        $meeting = $form->getData();
        $mapper = $this->getMeetingMapper();

        if ($mapper->isManaged($meeting)) {
            // meeting is already in the database
            $form->setMessages([
                'number' => ['Deze vergadering bestaat al'],
            ]);

            return null;
        }

        $mapper->persist($meeting);

        return $meeting;
    }

    /**
     * Search for organs by name.
     *
     * @return FoundationModel[]
     */
    public function organSearch(string $query): array
    {
        return $this->getOrganMapper()->organSearch($query);
    }

    /**
     * Search for decisions by name.
     *
     * @return DecisionModel[]
     */
    public function decisionSearch(string $query): array
    {
        return $this->getMeetingMapper()->searchDecision($query);
    }

    /**
     * Get the foundation of an organ.
     */
    public function findFoundation(
        MeetingTypes $type,
        int $meetingNumber,
        int $decisionPoint,
        int $decisionNumber,
        int $subdecisionNumber,
    ): ?FoundationModel {
        return $this->getOrganMapper()->find(
            $type,
            $meetingNumber,
            $decisionPoint,
            $decisionNumber,
            $subdecisionNumber,
        );
    }

    /**
     * Get the create meeting form.
     */
    public function getCreateMeetingForm(): CreateMeetingForm
    {
        return $this->createMeetingForm;
    }

    /**
     * Get the delete decision form.
     */
    public function getDeleteDecisionForm(): DeleteDecisionForm
    {
        return $this->deleteDecisionForm;
    }

    /**
     * Get the board install form.
     */
    public function getBoardInstallForm(): BoardInstallForm
    {
        return $this->boardInstallForm;
    }

    /**
     * Get the board release form.
     */
    public function getBoardReleaseForm(): BoardReleaseForm
    {
        return $this->boardReleaseForm;
    }

    /**
     * Get the board release form.
     */
    public function getBoardDischargeForm(): BoardDischargeForm
    {
        return $this->boardDischargeForm;
    }

    /**
     * Get install form.
     */
    public function getInstallForm(): InstallForm
    {
        return $this->installForm;
    }

    /**
     * Get abolish form.
     */
    public function getAbolishForm(): AbolishForm
    {
        return $this->abolishForm;
    }

    /**
     * Get the destroy form.
     */
    public function getDestroyForm(): DestroyForm
    {
        return $this->destroyForm;
    }

    /**
     * Get foundation form.
     */
    public function getFoundationForm(): FoundationForm
    {
        return $this->foundationForm;
    }

    /**
     * Get budget form.
     */
    public function getBudgetForm(): BudgetForm
    {
        return $this->budgetForm;
    }

    /**
     * Get key grant form.
     */
    public function getKeyGrantForm(): KeyGrantForm
    {
        return $this->keyGrantForm;
    }

    /**
     * Get key withdraw form.
     */
    public function getKeyWithdrawForm(): KeyWithdrawForm
    {
        return $this->keyWithdrawForm;
    }

    /**
     * Get other form.
     */
    public function getOtherForm(): OtherForm
    {
        return $this->otherForm;
    }

    /**
     * Get the export form.
     */
    public function getExportForm(): ExportForm
    {
        return $this->exportForm;
    }

    /**
     * Get the meeting mapper.
     */
    public function getMeetingMapper(): MeetingMapper
    {
        return $this->meetingMapper;
    }

    /**
     * Get the organ mapper.
     */
    public function getOrganMapper(): OrganMapper
    {
        return $this->organMapper;
    }
}
