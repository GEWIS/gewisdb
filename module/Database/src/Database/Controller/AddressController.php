<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class AddressController extends AbstractActionController
{
    public function indexAction()
    {
        return new ViewModel([
            'form' => $this->getMemberService()->getAddressExportForm()
        ]);
    }

    public function getMemberService()
    {
        return $this->getServiceLocator()->get('database_service_member');
    }
}
