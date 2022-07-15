<?php

namespace Database\Controller;

use Database\Form\Fieldset\MemberFunction as MemberFunctionFieldset;
use Database\Service\Meeting as MeetingService;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Laminas\View\Model\JsonModel;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

class MeetingController extends AbstractActionController
{
    /** @var MeetingService $meetingService */
    private $meetingService;

    /** @var MemberFunctionFieldset $memberFunctionFieldset */
    private $memberFunctionFieldset;

    /**
     * @param MeetingService $meetingService
     * @param MemberFunctionFieldset $memberFunctionFieldset
     */
    public function __construct(
        MeetingService $meetingService,
        MemberFunctionFieldset $memberFunctionFieldset,
    ) {
        $this->meetingService = $meetingService;
        $this->memberFunctionFieldset = $memberFunctionFieldset;
    }

    /**
     * Index action.
     *
     * Shows all meetings.
     */
    public function indexAction()
    {
        return new ViewModel([
            'meetings' => $this->meetingService->getAllMeetings(),
        ]);
    }

    /**
     * Create a new meeting.
     */
    public function createAction()
    {
        $request = $this->getRequest();

        if ($request->isPost()) {
            $meeting = $this->meetingService->createMeeting($request->getPost());
            if (null !== $meeting) {
                return $this->redirect()->toRoute('meeting/view', [
                    'type' => $meeting->getType()->value,
                    'number' => $meeting->getNumber(),
                ]);
            }
        }

        return new ViewModel([
            'form' => $this->meetingService->getCreateMeetingForm(),
        ]);
    }

    /**
     * View a meeting.
     */
    public function viewAction()
    {
        return new ViewModel([
            'type' => $this->params()->fromRoute('type'),
            'number' => $this->params()->fromRoute('number'),
            'meeting' => $this->meetingService->getMeeting(
                $this->params()->fromRoute('type'),
                $this->params()->fromRoute('number'),
            ),
        ]);
    }

    /**
     * Decision action.
     */
    public function decisionAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');
        $point = $this->params()->fromRoute('point');
        $decision = $this->params()->fromRoute('decision');

        $meeting = $this->meetingService->getMeeting($type, $number);

        if ($this->meetingService->decisionExists($type, $number, $point, $decision)) {
            return new ViewModel([
                'meeting' => $meeting,
                'point' => $point,
                'decision' => $decision,
                'error' => true,
            ]);
        }

        return new ViewModel([
            'meeting' => $meeting,
            'point' => $point,
            'decision' => $decision,
            'forms' => $this->getDecisionForms($meeting, $point, $decision),
            'installs' => $this->meetingService->getCurrentBoard(),
            'memberfunction' => $this->memberFunctionFieldset,
        ]);
    }

    /**
     * Get all forms for a decision action.
     *
     * @param Meeting $meeting
     * @param int $point
     * @param int $decision
     *
     * @return array
     */
    protected function getDecisionForms($meeting, $point, $decision)
    {
        $forms = [
            'budget' => $this->meetingService->getBudgetForm(),
            'foundation' => $this->meetingService->getFoundationForm(),
            'abolish' => $this->meetingService->getAbolishForm(),
            'destroy' => $this->meetingService->getDestroyForm(),
            'install' => $this->meetingService->getInstallForm(),
            'other' => $this->meetingService->getOtherForm(),
            'board_install' => $this->meetingService->getBoardInstallForm(),
            'board_release' => $this->meetingService->getBoardReleaseForm(),
            'board_discharge' => $this->meetingService->getBoardDischargeForm(),
        ];

        foreach ($forms as $form) {
            $form->setDecisionData($meeting, $point, $decision);
        }

        return $forms;
    }


    /**
     * Decision form action.
     */
    public function decisionformAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute('meeting');
        }

        switch ($this->params()->fromRoute('form')) {
            case 'budget':
                return new ViewModel(
                    $this->meetingService->budgetDecision($this->getRequest()->getPost()),
                );
                break;
            case 'foundation':
                return new ViewModel(
                    $this->meetingService->foundationDecision($this->getRequest()->getPost()),
                );
                break;
            case 'abolish':
                return new ViewModel(
                    $this->meetingService->abolishDecision($this->getRequest()->getPost()),
                );
                break;
            case 'install':
                return new ViewModel(
                    $this->meetingService->installDecision($this->getRequest()->getPost()),
                );
                break;
            case 'destroy':
                return new ViewModel(
                    $this->meetingService->destroyDecision($this->getRequest()->getPost()),
                );
                break;
            case 'board_install':
                return new ViewModel(
                    $this->meetingService->boardInstallDecision($this->getRequest()->getPost()),
                );
                break;
            case 'board_release':
                return new ViewModel(
                    $this->meetingService->boardReleaseDecision($this->getRequest()->getPost()),
                );
                break;
            case 'board_discharge':
                return new ViewModel(
                    $this->meetingService->boardDischargeDecision($this->getRequest()->getPost()),
                );
                break;
            case 'other':
                return new ViewModel(
                    $this->meetingService->otherDecision($this->getRequest()->getPost()),
                );
                break;
            default:
                return $this->redirect()->toRoute('meeting');
                break;
        }

        return new ViewModel([]);
    }

    /**
     * Delete a decision.
     */
    public function deleteAction()
    {
        $type = $this->params()->fromRoute('type');
        $number = $this->params()->fromRoute('number');
        $point = $this->params()->fromRoute('point');
        $decision = $this->params()->fromRoute('decision');

        if ($this->getRequest()->isPost()) {
            try {
                if (
                    $this->meetingService->deleteDecision(
                        $this->getRequest()->getPost(),
                        $type,
                        $number,
                        $point,
                        $decision,
                    )
                ) {
                    return new ViewModel([
                        'type' => $type,
                        'number' => $number,
                        'point' => $point,
                        'decision' => $decision,
                    ]);
                }
            } catch (ForeignKeyConstraintViolationException $e) {
                return new ViewModel([
                    'error' => true,
                    'exception' => $e,
                ]);
            }
            // Not deleted
            // TODO: redirect back to meeting
        }

        return new ViewModel([
            'form' => $this->meetingService->getDeleteDecisionForm(),
            'type' => $type,
            'number' => $number,
            'point' => $point,
            'decision' => $decision,
        ]);
    }

    /**
     * Search action.
     *
     * Uses JSON to search for decisions.
     */
    public function searchAction()
    {
        $query = $this->params()->fromQuery('q');
        $res = $this->meetingService->decisionSearch($query);

        $res = array_map(function ($decision) {
            return $decision->toArray();
        }, $res);

        return new JsonModel([
            'json' => $res,
        ]);
    }
}
