<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class ExportController extends AbstractActionController
{

    /**
     * Index action.
     */
    public function indexAction()
    {
        $service = $this->getMeetingService();

        return new ViewModel(array(
            'form' => $service->getExportForm()
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
