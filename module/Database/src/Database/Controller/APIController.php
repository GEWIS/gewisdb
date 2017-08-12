<?php

namespace Database\Controller;


use Zend\Mvc\Controller\AbstractActionController;
use \Zend\Json\Json;
use Zend\View\Model\JsonModel;

class APIController extends AbstractActionController
{
    public function updateMemberAction()
    {
        $update = Json::decode ($this->getRequest()->getContent(), Json::TYPE_ARRAY);
        $updateService = $this->getServiceLocator()->get('database_service_update');
        $updateService->storeMemberUpdateRequest($this->params('lidnr'), $update);
        return new JsonModel(['success' => true]);
    }
}