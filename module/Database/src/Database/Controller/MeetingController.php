<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;

class MeetingController extends AbstractActionController
{
    /**
     * Index action.
     *
     * Shows all meetings.
     */
    public function indexAction()
    {
        $service = $this->getMeetingService();

        return new ViewModel([
            'meetings' => $service->getAllMeetings()
        ]);
    }

    /**
     * Create a new meeting.
     */
    public function createAction()
    {
        $service = $this->getMeetingService();
        $request = $this->getRequest();

        if ($request->isPost()) {
            $meeting = $service->createMeeting($request->getPost());
            if (null !== $meeting) {
                return $this->redirect()->toRoute('meeting/view', [
                    'type' => $meeting->getType(),
                    'number' => $meeting->getNumber()
                ]);
            }
        }

        return new ViewModel([
            'form' => $service->getCreateMeetingForm()
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
            'meeting' => $this->getMeetingService()->getMeeting(
                $this->params()->fromRoute('type'),
                $this->params()->fromRoute('number')
            )
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

        $meeting = $this->getMeetingService()
                        ->getMeeting($type, $number);

        if ($this->getMeetingService()->decisionExists($type, $number, $point, $decision)) {
            return new ViewModel([
                'meeting' => $meeting,
                'point' => $point,
                'decision' => $decision,
                'error' => true
            ]);
        }

        return new ViewModel([
            'meeting' => $meeting,
            'point' => $point,
            'decision' => $decision,
            'forms' => $this->getDecisionForms($meeting, $point, $decision),
            'memberfunction' => $this->getServiceLocator()->get('database_form_fieldset_memberfunction_nomember')
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
            'budget' => $this->getMeetingService()->getBudgetForm(),
            'foundation' => $this->getMeetingService()->getFoundationForm(),
            'abolish' => $this->getMeetingService()->getAbolishForm(),
            'destroy' => $this->getMeetingService()->getDestroyForm(),
            'install' => $this->getMeetingService()->getInstallForm(),
            'other' => $this->getMeetingService()->getOtherForm(),
            'board_install' => $this->getMeetingService()->getBoardInstallForm(),
            'board_release' => $this->getMeetingService()->getBoardReleaseForm(),
            'board_discharge' => $this->getMeetingService()->getBoardDischargeForm(),
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

        $service = $this->getMeetingService();

        switch ($this->params()->fromRoute('form')) {
            case 'budget':
                return new ViewModel(
                    $service->budgetDecision($this->getRequest()->getPost())
                );
            break;
            case 'foundation':
                return new ViewModel(
                    $service->foundationDecision($this->getRequest()->getPost())
                );
            break;
            case 'abolish':
                return new ViewModel(
                    $service->abolishDecision($this->getRequest()->getPost())
                );
            break;
            case 'install':
                return new ViewModel(
                    $service->installDecision($this->getRequest()->getPost())
                );
            break;
            case 'destroy':
                return new ViewModel(
                    $service->destroyDecision($this->getRequest()->getPost())
                );
            break;
            case 'board_install':
                return new ViewModel(
                    $service->boardInstallDecision($this->getRequest()->getPost())
                );
            break;
            case 'board_release':
                return new ViewModel(
                    $service->boardReleaseDecision($this->getRequest()->getPost())
                );
            break;
            case 'board_discharge':
                return new ViewModel(
                    $service->boardDischargeDecision($this->getRequest()->getPost())
                );
            break;
            case 'other':
                return new ViewModel(
                    $service->otherDecision($this->getRequest()->getPost())
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

        $service = $this->getMeetingService();

        if ($this->getRequest()->isPost()) {
            try {
                if (
                    $service->deleteDecision(
                        $this->getRequest()->getPost(),
                        $type,
                        $number,
                        $point,
                        $decision
                    )
                ) {
                    return new ViewModel([
                    'type' => $type,
                    'number' => $number,
                    'point' => $point,
                    'decision' => $decision
                    ]);
                }
            } catch (ForeignKeyConstraintViolationException $e) {
                return new ViewModel([
                    'error' => true,
                    'exception' => $e
                ]);
            }
            // Not deleted
            // TODO: redirect back to meeting
        }
        return new ViewModel([
            'form' => $this->getMeetingService()->getDeleteDecisionForm(),
            'type' => $type,
            'number' => $number,
            'point' => $point,
            'decision' => $decision
        ]);
    }

    /**
     * Search action.
     *
     * Uses JSON to search for decisions.
     */
    public function searchAction()
    {
        $service = $this->getMeetingService();

        $query = $this->params()->fromQuery('q');

        $res = $service->decisionSearch($query);

        $res = array_map(function ($decision) {
            return $decision->toArray();
        }, $res);

        return new JsonModel([
            'json' => $res
        ]);
    }

    /**
     * Get the meeting service.
     *
     * @return \Database\Service\Meeting
     */
    public function getMeetingService()
    {
        return $this->getServiceLocator()->get('database_service_meeting');
    }
}
