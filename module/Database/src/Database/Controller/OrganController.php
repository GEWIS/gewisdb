<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class OrganController extends AbstractActionController
{

    /**
     * Index action, for organ search.
     *
     * Shows all meetings.
     */
    public function indexAction()
    {
        $service = $this->getMeetingService();

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
