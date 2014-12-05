<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

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

        return new ViewModel(array(
            'meetings' => $service->getAllMeetings()
        ));
    }

    /**
     * Create a new meeting.
     */
    public function createAction()
    {
        $service = $this->getMeetingService();
        $request = $this->getRequest();

        if ($request->isPost() && $service->createMeeting($request->getPost())) {
            return new ViewModel(array(
                'success' => true
            ));
        }

        return new ViewModel(array(
            'form' => $service->getCreateMeetingForm()
        ));
    }

    /**
     * View a meeting.
     */
    public function viewAction()
    {
        return new ViewModel(array(
            'type' => $this->params()->fromRoute('type'),
            'number' => $this->params()->fromRoute('number'),
            'meeting' => $this->getMeetingService()->getMeeting(
                $this->params()->fromRoute('type'),
                $this->params()->fromRoute('number')
            )
        ));
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

        return new ViewModel(array(
            'meeting' => $meeting,
            'point' => $point,
            'decision' => $decision,
            'forms' => $this->getDecisionForms($meeting, $point, $decision),
            'memberfunction' => $this->getServiceLocator()->get('database_form_fieldset_memberfunction_nomember')
        ));
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
        $forms = array(
            'budget' => $this->getMeetingService()->getBudgetForm(),
            'foundation' => $this->getMeetingService()->getFoundationForm(),
            'abolish' => $this->getMeetingService()->getAbolishForm(),
            'destroy' => $this->getMeetingService()->getDestroyForm(),
            'install' => $this->getMeetingService()->getInstallForm(),
            'other' => $this->getMeetingService()->getOtherForm()
        );

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
            $this->redirect()->toRoute('meeting/decision');
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
        case 'other':
            return new ViewModel(
                $service->otherDecision($this->getRequest()->getPost())
            );
            break;
        default:
            $this->redirect()->toRoute('meeting/decision');
            break;
        }
        return new ViewModel(array());
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
            if ($service->deleteDecision($this->getRequest()->getPost(),
                    $type, $number, $point, $decision)
            ) {
                return new ViewModel(array(
                    'type' => $type,
                    'number' => $number,
                    'point' => $point,
                    'decision' => $decision
                ));
            }
            // Not deleted
            // TODO: redirect back to meeting
        }
        return new ViewModel(array(
            'form' => $this->getMeetingService()->getDeleteDecisionForm(),
            'type' => $type,
            'number' => $number,
            'point' => $point,
            'decision' => $decision
        ));
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

        return new JsonModel(array(
            'json' => $res
        ));
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
