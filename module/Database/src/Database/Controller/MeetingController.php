<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

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
        $budgetform = $this->getMeetingService()->getBudgetForm();

        $budgetform->setDecisionData($meeting, $point, $decision);

        return new ViewModel(array(
            'meeting' => $meeting,
            'point' => $point,
            'decision' => $decision,
            'budgetform' => $this->getMeetingService()->getBudgetForm(),
            'foundationform' => $this->getMeetingService()->getFoundationForm(),
            'abolishform' => $this->getMeetingService()->getAbolishForm(),
            'installform' => $this->getMeetingService()->getInstallForm(),
        ));
    }

    /**
     * Decision form action.
     */
    public function decisionformAction()
    {
        if (!$this->getRequest()->isPost()) {
            $this->redirect()->toRoute('meeting/decision');
        }
        switch ($this->params()->fromRoute('form')) {
        case 'budget':
            $this->getMeetingService()->budgetDecision($this->getRequest()->getPost());
            break;
        case 'foundation':
            break;
        case 'abolish':
            break;
        case 'install':
            break;
        default:
            $this->redirect()->toRoute('meeting/decision');
            break;
        }
        return new ViewModel(array());
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
