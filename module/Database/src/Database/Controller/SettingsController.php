<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

use Database\Model\Member;

class SettingsController extends AbstractActionController
{

    /**
     * Index action.
     */
    public function indexAction()
    {
        return new ViewModel(array());
    }

    /**
     * Function action.
     */
    public function functionAction()
    {
        return new ViewModel(array(
            'functions' => $this->getFunctionService()->getAllFunctions(),
            'form' => $this->getFunctionService()->getFunctionForm()
        ));
    }

    /**
     * Get the function service.
     *
     * @return \Database\Service\Function
     */
    public function getFunctionService()
    {
        return $this->getServiceLocator()->get('database_service_installationfunction');
    }
}
