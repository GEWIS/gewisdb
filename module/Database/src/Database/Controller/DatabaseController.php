<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class DatabaseController extends AbstractActionController
{

    /**
     * Index action.
     */
    public function indexAction()
    {
        return new ViewModel(array());
    }

    /**
     * Create a new meeting.
     */
    public function createAction()
    {
        return new ViewModel(array(
            'form' => $this->getServiceLocator()->get('database_form_createmeeting')
        ));
    }
}
