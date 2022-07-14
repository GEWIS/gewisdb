<?php

namespace Database\Service;

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
use Database\Form\Other as OtherForm;
use Database\Mapper\Meeting as MeetingMapper;
use Database\Mapper\Organ as OrganMapper;
use Database\Model\Meeting as MeetingModel;
use Database\Model\Decision;
use Database\Model\SubDecision\Foundation as FoundationModel;
use ReflectionObject;
use Laminas\Stdlib\PriorityQueue;

class Meeting
{
    /** @var AbolishForm $abolishForm */
    private $abolishForm;

    /** @var BoardDischargeForm $boardDischargeForm */
    private $boardDischargeForm;

    /** @var BoardInstallForm $boardInstallForm */
    private $boardInstallForm;

    /** @var BoardReleaseForm $boardReleaseForm */
    private $boardReleaseForm;

    /** @var BudgetForm $budgetForm */
    private $budgetForm;

    /** @var CreateMeetingForm $createMeetingForm */
    private $createMeetingForm;

    /** @var DeleteDecisionForm $deleteDecisionForm */
    private $deleteDecisionForm;

    /** @var DestroyForm $destroyForm */
    private $destroyForm;

    /** @var ExportForm $exportForm */
    private $exportForm;

    /** @var FoundationForm $foundationForm */
    private $foundationForm;

    /** @var InstallForm $installForm */
    private $installForm;

    /** @var OtherForm $otherForm */
    private $otherForm;

    /** @var MeetingMapper $meetingMapper */
    private $meetingMapper;

    /** @var OrganMapper $organMapper */
    private $organMapper;

    /**
     * @param AbolishForm $abolishForm
     * @param BoardDischargeForm $boardDischargeForm
     * @param BoardInstallForm $boardInstallForm
     * @param BoardReleaseForm $boardReleaseForm
     * @param BudgetForm $budgetForm
     * @param CreateMeetingForm $createMeetingForm
     * @param DeleteDecisionForm $deleteDecisionForm
     * @param DestroyForm $destroyForm
     * @param ExportForm $exportForm
     * @param FoundationForm $foundationForm
     * @param InstallForm $installForm
     * @param OtherForm $otherForm
     * @param MeetingMapper $meetingMapper
     * @param OrganMapper $organMapper
     */
    public function __construct(
        AbolishForm $abolishForm,
        BoardDischargeForm $boardDischargeForm,
        BoardInstallForm $boardInstallForm,
        BoardReleaseForm $boardReleaseForm,
        BudgetForm $budgetForm,
        CreateMeetingForm $createMeetingForm,
        DeleteDecisionForm $deleteDecisionForm,
        DestroyForm $destroyForm,
        ExportForm $exportForm,
        FoundationForm $foundationForm,
        InstallForm $installForm,
        OtherForm $otherForm,
        MeetingMapper $meetingMapper,
        OrganMapper $organMapper
    ) {
        $this->abolishForm = $abolishForm;
        $this->boardDischargeForm = $boardDischargeForm;
        $this->boardInstallForm = $boardInstallForm;
        $this->boardReleaseForm = $boardReleaseForm;
        $this->budgetForm = $budgetForm;
        $this->createMeetingForm = $createMeetingForm;
        $this->deleteDecisionForm = $deleteDecisionForm;
        $this->destroyForm = $destroyForm;
        $this->exportForm = $exportForm;
        $this->foundationForm = $foundationForm;
        $this->installForm = $installForm;
        $this->otherForm = $otherForm;
        $this->meetingMapper = $meetingMapper;
        $this->organMapper = $organMapper;
    }

    /**
     * Get a meeting.
     *
     * @param string $type
     * @param int $number
     *
     * @return MeetingModel
     */
    public function getMeeting($type, $number)
    {
        return $this->getMeetingMapper()->find($type, $number);
    }

    /**
     * Get all meetings.
     *
     * @todo pagination
     *
     * @return array All meetings.
     */
    public function getAllMeetings()
    {
        return $this->getMeetingMapper()->findAll();
    }

    /**
     * Find decisions by meetings.
     *
     * @param array $meetings
     *
     * @return array Of decisions.
     */
    public function getDecisionsByMeetings($meetings)
    {
        $mapper = $this->getMeetingMapper();

        return $mapper->findDecisionsByMeetings($meetings);
    }

    /**
     * Check if the decision exists.
     *
     * @param string $type
     * @param int $number
     * @param int $point
     * @param int $decision
     *
     * @return boolean
     */
    public function decisionExists($type, $number, $point, $decision)
    {
        $mapper = $this->getMeetingMapper();

        return null !== $mapper->findDecision($type, $number, $point, $decision);
    }

    /**
     * Get the current board installations.
     *
     * @return array
     */
    public function getCurrentBoard()
    {
        return $this->getMeetingMapper()->findCurrentBoard();
    }

    /**
     * Export decisions.
     *
     * @param array $data
     *
     * @return array
     */
    public function export($data)
    {
        $form = $this->getExportForm();

        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        // extract the meetings
        $data = $form->getData();
        $meetings = array();
        foreach ($data['meetings'] as $meeting) {
            $meeting = explode('-', $meeting);
            $meetings[] = array(
                'type' => $meeting[0],
                'number' => $meeting[1]
            );
        }

        // find meeting data
        return $this->getDecisionsByMeetings($meetings);
    }

    /**
     * Destroy decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function destroyDecision($data)
    {
        $form = $this->getDestroyForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'destroy',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'destroy',
            'decision' => $decision
        );
    }

    /**
     * Delete a decision.
     *
     * @param array $data
     * @param string $type
     * @param int $number
     * @param int $point
     * @param int $decision
     *
     * @return boolean
     */
    public function deleteDecision($data, $type, $number, $point, $decision)
    {
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
     * @param array $data
     *
     * @return array
     */
    public function otherDecision($data)
    {
        $form = $this->getOtherForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'other',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'other',
            'decision' => $decision
        );
    }

    /**
     * Abolish decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function abolishDecision($data)
    {
        $form = $this->getAbolishForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'abolish',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'foundation',
            'decision' => $decision
        );
    }

    /**
     * Board install decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function boardInstallDecision($data)
    {
        $form = $this->getBoardInstallForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'board_install',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'board_install',
            'decision' => $decision
        );
    }

    /**
     * Board discharge decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function boardDischargeDecision($data)
    {
        $form = $this->getBoardDischargeForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'board_discharge',
                'installs' => $this->getCurrentBoard(),
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'board_discharge',
            'decision' => $decision
        );
    }

    /**
     * Board release decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function boardReleaseDecision($data)
    {
        $form = $this->getBoardReleaseForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'board_release',
                'installs' => $this->getCurrentBoard(),
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'board_release',
            'decision' => $decision
        );
    }

    /**
     * Install decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function installDecision($data)
    {
        $form = $this->getInstallForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'install',
                'form' => $form
            );
        }

        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'install',
            'decision' => $decision
        );
    }

    /**
     * Foundation decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function foundationDecision($data)
    {
        $form = $this->getFoundationForm();

        $form->setData($data);
        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'foundation',
                'form' => $form
            );
        }


        $decision = $form->getData();

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'foundation',
            'decision' => $decision
        );
    }

    /**
     * Budget decision.
     *
     * @param array $data
     *
     * @return array
     */
    public function budgetDecision($data)
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

        $form->bind(new Decision());

        if (!$form->isValid()) {
            return array(
                'type' => 'budget',
                'form' => $form
            );
        }

        $decision = $form->getData();

        foreach ($decision->getSubdecisions() as $sub) {
            // use hack to make sure approval and changes are booleans
            if ($sub->getApproval() === 'false') {
                $sub->setApproval(false);
            }
            if ($sub->getChanges() === 'false') {
                $sub->setChanges(false);
            }
        }

        // simply persist through the meeting mapper
        $this->getMeetingMapper()->persist($decision->getMeeting());

        return array(
            'type' => 'budget',
            'decision' => $decision
        );
    }

    /**
     * Create a meeting.
     *
     * @param array $data Meeting creation data.
     *
     * @return MeetingModel or null if creation was succesfull
     */
    public function createMeeting($data)
    {
        $form = $this->getCreateMeetingForm();
        $form->bind(new MeetingModel());
        $form->setData($data);

        if (!$form->isValid()) {
            return null;
        }

        $meeting = $form->getData();
        $mapper = $this->getMeetingMapper();

        if ($mapper->isManaged($meeting)) {
            // meeting is already in the database
            $form->setMessages(array(
                'number' => array(
                    'Deze vergadering bestaat al'
                )
            ));
            return null;
        }

        $mapper->persist($meeting);

        return $meeting;
    }

    /**
     * Search for organs by name.
     *
     * @param string $query
     *
     * @return array organ (decisions)
     */
    public function organSearch($query)
    {
        return $this->getOrganMapper()->organSearch($query);
    }

    /**
     * Search for decisions by name.
     * @param string $query
     * @return array Decisions
     */
    public function decisionSearch($query)
    {
        return $this->getMeetingMapper()->searchDecision($query);
    }

    /**
     * Get the foundation of an organ.
     *
     * @param string $type
     * @param string $meetingNumber
     * @param string $decisionPoint
     * @param string $decisionNumber
     * @param string $subdecisionNumber
     *
     * @return FoundationModel
     */
    public function findFoundation($type, $meetingNumber, $decisionPoint, $decisionNumber, $subdecisionNumber)
    {
        return $this->getOrganMapper()->find(
            $type,
            $meetingNumber,
            $decisionPoint,
            $decisionNumber,
            $subdecisionNumber
        );
    }

    /**
     * Get the create meeting form.
     *
     * @return CreateMeetingForm
     */
    public function getCreateMeetingForm(): CreateMeetingForm
    {
        return $this->createMeetingForm;
    }

    /**
     * Get the delete decision form.
     *
     * @return DeleteDecisionForm
     */
    public function getDeleteDecisionForm(): DeleteDecisionForm
    {
        return $this->deleteDecisionForm;
    }

    /**
     * Get the board install form.
     *
     * @return BoardInstallForm
     */
    public function getBoardInstallForm(): BoardInstallForm
    {
        return $this->boardInstallForm;
    }

    /**
     * Get the board release form.
     *
     * @return BoardReleaseForm
     */
    public function getBoardReleaseForm(): BoardReleaseForm
    {
        return $this->boardReleaseForm;
    }

    /**
     * Get the board release form.
     *
     * @return BoardDischargeForm
     */
    public function getBoardDischargeForm(): BoardDischargeForm
    {
        return $this->boardDischargeForm;
    }

    /**
     * Get install form.
     *
     * @return InstallForm
     */
    public function getInstallForm(): InstallForm
    {
        return $this->installForm;
    }

    /**
     * Get abolish form.
     *
     * @return AbolishForm
     */
    public function getAbolishForm(): AbolishForm
    {
        return $this->abolishForm;
    }

    /**
     * Get the destroy form.
     *
     * @return DestroyForm
     */
    public function getDestroyForm(): DestroyForm
    {
        return $this->destroyForm;
    }

    /**
     * Get foundation form.
     *
     * @return FoundationForm
     */
    public function getFoundationForm(): FoundationForm
    {
        return $this->foundationForm;
    }

    /**
     * Get budget form.
     *
     * @return BudgetForm
     */
    public function getBudgetForm(): BudgetForm
    {
        return $this->budgetForm;
    }

    /**
     * Get other form.
     *
     * @return OtherForm
     */
    public function getOtherForm(): OtherForm
    {
        return $this->otherForm;
    }

    /**
     * Get the export form.
     *
     * @return ExportForm
     */
    public function getExportForm(): ExportForm
    {
        return $this->exportForm;
    }

    /**
     * Get the meeting mapper.
     *
     * @return MeetingMapper
     */
    public function getMeetingMapper(): MeetingMapper
    {
        return $this->meetingMapper;
    }

    /**
     * Get the organ mapper.
     *
     * @return OrganMapper
     */
    public function getOrganMapper(): OrganMapper
    {
        return $this->organMapper;
    }
}
