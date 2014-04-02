<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\View\Model\JsonModel;

class OrganController extends AbstractActionController
{

    /**
     * Index action, for organ search.
     */
    public function indexAction()
    {
        return new ViewModel(array());
    }

    /**
     * Search action.
     *
     * Uses JSON to search for members.
     */
    public function searchAction()
    {
        $service = $this->getMeetingService();

        $query = $this->params()->fromQuery('q');

        $res = $service->organSearch($query);

        $res = array_map(function ($organ) {
            return array(
                'name' => $organ->getName()
            );
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
