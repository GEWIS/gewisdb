<?php

namespace Database\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class QueryController extends AbstractActionController
{

    /**
     * Index action.
     */
    public function indexAction()
    {
        $service = $this->getQueryService();

        return new ViewModel(array(
            'form' => $service->getQueryForm()
        ));
    }

    /**
     * Get the query service.
     *
     * @return \Database\Service\Query
     */
    public function getQueryService()
    {
        return $this->getServiceLocator()->get('database_service_query');
    }
}
